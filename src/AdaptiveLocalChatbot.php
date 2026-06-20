<?php

declare(strict_types=1);

require_once __DIR__ . '/ChatbotSecurity.php';
require_once __DIR__ . '/ResponseFactory.php';
require_once __DIR__ . '/OllamaConnector.php';
require_once __DIR__ . '/Adapters/AdapterInterface.php';
require_once __DIR__ . '/Adapters/GenericPdoAdapter.php';
require_once __DIR__ . '/Engines/LocalRulesEngine.php';

final class AdaptiveLocalChatbot
{
    private PDO $pdo;
    private array $config;
    /** @var AdapterInterface[] */
    private array $adapters;

    public function __construct(PDO $pdo, array $config = [], array $adapters = [])
    {
        $this->pdo = $pdo;
        $this->config = array_merge($this->defaultConfig(), $config);
        $this->adapters = $adapters ?: [GenericPdoAdapter::fromConfig($pdo, $this->config)];
    }

    public function handle(array $input): array
    {
        $action = strtolower(trim((string)($input['action'] ?? 'message')));
        $message = trim((string)($input['message'] ?? ''));
        $language = ChatbotSecurity::detectLanguage($message, (string)($input['language'] ?? ''));

        if ($action === 'health') {
            return ResponseFactory::make(0, $language, 'Adaptive Local Chatbot is ready.', 'health', 1.0, [
                'version' => (string)($this->config['version'] ?? '0.2.0'),
                'runtime' => (string)($this->config['provider_mode'] ?? 'local_rules'),
                'ollama_enabled' => $this->shouldUseOllama(),
                'capabilities' => ['message', 'health', 'local_rules', 'read_only_adapters', 'optional_ollama'],
            ]);
        }

        if ($action === 'history') {
            return $this->historyResponse((int)($input['session_id'] ?? 0), $language);
        }

        $sessionId = $this->sessionId((int)($input['session_id'] ?? 0), $input);

        if ($message === '') {
            return ResponseFactory::empty($sessionId, $language);
        }

        $this->saveMessage($sessionId, 'user', $message, []);

        if (ChatbotSecurity::looksLikeSecretRequest($message)) {
            $response = ResponseFactory::blocked($sessionId, $language, 'secrets');
            $this->saveAssistant($sessionId, $response);
            return $response;
        }

        if (ChatbotSecurity::looksLikeWriteAction($message)) {
            $response = ResponseFactory::blocked($sessionId, $language, 'write_action');
            $this->saveAssistant($sessionId, $response);
            $this->logTool($sessionId, 'blocked_write', ['message' => $message], $response['answer']);
            return $response;
        }

        $engine = new LocalRulesEngine($this->pdo, $this->config, $this->adapters);
        $response = $engine->answer($message, $input, $language);

        if ($this->shouldAskOllama($response)) {
            $ollama = new OllamaConnector($this->config);
            $modelResponse = $ollama->complete($message, $response);
            if ($modelResponse['success']) {
                $response['answer'] = $modelResponse['answer'];
                $response['tool'] = 'ollama_assisted';
                $response['confidence'] = max((float)$response['confidence'], (float)($modelResponse['confidence'] ?? 0.72));
                $response['sources'][] = [
                    'label' => 'Local Ollama model',
                    'ref' => 'ollama:' . (string)($this->config['ollama_model'] ?? ''),
                    'type' => 'local_model',
                ];
            }
        }

        $this->saveAssistant($sessionId, $response);
        $this->logTool($sessionId, (string)$response['tool'], ['message' => $message], (string)$response['answer']);

        return $response;
    }

    private function defaultConfig(): array
    {
        return [
            'enabled' => true,
            'version' => '0.2.0',
            'provider_mode' => 'local_rules',
            'ollama_base_url' => 'http://127.0.0.1:11434',
            'ollama_model' => '',
            'ollama_timeout' => 30,
            'ollama_temperature' => 0,
            'max_results' => 8,
            'save_chat_history' => true,
            'allow_public_history' => false,
            'allow_ollama' => false,
            'tables' => [],
            'handoff_label' => 'Human support',
        ];
    }

    private function shouldUseOllama(): bool
    {
        return (bool)($this->config['allow_ollama'] ?? false)
            && (string)($this->config['provider_mode'] ?? 'local_rules') === 'ollama'
            && trim((string)($this->config['ollama_model'] ?? '')) !== '';
    }

    private function shouldAskOllama(array $response): bool
    {
        return $this->shouldUseOllama()
            && (float)($response['confidence'] ?? 0) < 0.85;
    }

    private function historyResponse(int $sessionId, string $language): array
    {
        if (!(bool)($this->config['allow_public_history'] ?? false)) {
            return ResponseFactory::blocked($sessionId, $language, 'history_disabled');
        }

        if ($sessionId <= 0) {
            return ResponseFactory::fallback(0, $language);
        }

        $stmt = $this->pdo->prepare('SELECT role, message_text, metadata_json, created_at FROM chatbot_messages WHERE session_id = :session_id ORDER BY id ASC LIMIT 100');
        $stmt->execute(['session_id' => $sessionId]);

        return ResponseFactory::make($sessionId, $language, 'History loaded.', 'history', 0.9, [
            'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        ]);
    }

    private function sessionId(int $incoming, array $input): int
    {
        if ($incoming > 0) {
            return $incoming;
        }

        $title = trim((string)($input['context_title'] ?? 'Chat session'));
        $stmt = $this->pdo->prepare('INSERT INTO chatbot_sessions (title, context_type, context_id, context_title, created_at, updated_at) VALUES (:title, :context_type, :context_id, :context_title, NOW(), NOW())');
        $stmt->execute([
            'title' => ChatbotSecurity::slice($title !== '' ? $title : 'Chat session', 0, 190),
            'context_type' => (string)($input['context_type'] ?? ''),
            'context_id' => (string)($input['context_id'] ?? ''),
            'context_title' => (string)($input['context_title'] ?? ''),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function saveMessage(int $sessionId, string $role, string $message, array $metadata): void
    {
        if (!(bool)($this->config['save_chat_history'] ?? true)) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO chatbot_messages (session_id, role, message_text, metadata_json, created_at) VALUES (:session_id, :role, :message_text, :metadata_json, NOW())');
        $stmt->execute([
            'session_id' => $sessionId,
            'role' => $role,
            'message_text' => $message,
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function saveAssistant(int $sessionId, array $response): void
    {
        $this->saveMessage($sessionId, 'assistant', (string)$response['answer'], $response);
    }

    private function logTool(int $sessionId, string $tool, array $arguments, string $summary): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO chatbot_tool_runs (session_id, tool_name, arguments_json, result_summary, created_at) VALUES (:session_id, :tool_name, :arguments_json, :result_summary, NOW())');
        $stmt->execute([
            'session_id' => $sessionId,
            'tool_name' => $tool,
            'arguments_json' => json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'result_summary' => ChatbotSecurity::slice($summary, 0, 2000),
        ]);
    }
}
