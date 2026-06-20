<?php

declare(strict_types=1);

final class OllamaConnector
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function complete(string $message, array $localResponse): array
    {
        $baseUrl = rtrim((string)($this->config['ollama_base_url'] ?? ''), '/');
        $model = trim((string)($this->config['ollama_model'] ?? ''));
        if ($baseUrl === '' || $model === '') {
            return ['success' => false, 'answer' => ''];
        }

        $system = implode("\n", [
            'You are a local assistant enhancement layer.',
            'Use only the local retrieved answer, cards, tables, calculations, and sources.',
            'Do not invent facts. Do not reveal secrets. Do not execute tools.',
            'Return concise JSON that matches the requested schema.',
        ]);

        $schema = [
            'type' => 'object',
            'properties' => [
                'answer' => ['type' => 'string'],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['answer', 'confidence'],
        ];

        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => json_encode([
                    'question' => $message,
                    'local_response' => [
                        'answer' => (string)($localResponse['answer'] ?? ''),
                        'confidence' => (float)($localResponse['confidence'] ?? 0),
                        'sources' => $localResponse['sources'] ?? [],
                        'cards' => $localResponse['cards'] ?? [],
                        'table_rows' => $localResponse['table_rows'] ?? [],
                        'calculations' => $localResponse['calculations'] ?? [],
                    ],
                    'schema' => $schema,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            'stream' => false,
            'format' => $schema,
            'options' => [
                'temperature' => (float)($this->config['ollama_temperature'] ?? 0),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => max(5, (int)($this->config['ollama_timeout'] ?? 30)),
            ],
        ]);

        $raw = @file_get_contents($baseUrl . '/api/chat', false, $context);
        if (!is_string($raw) || $raw === '') {
            return ['success' => false, 'answer' => ''];
        }

        $data = json_decode($raw, true);
        $content = trim((string)($data['message']['content'] ?? ''));
        if ($content === '') {
            return ['success' => false, 'answer' => ''];
        }

        $structured = json_decode($content, true);
        $answer = is_array($structured) ? trim((string)($structured['answer'] ?? '')) : $content;
        $confidence = is_array($structured) ? (float)($structured['confidence'] ?? 0.72) : 0.72;

        return [
            'success' => $answer !== '',
            'answer' => $answer,
            'confidence' => max(0.0, min(1.0, $confidence)),
        ];
    }
}
