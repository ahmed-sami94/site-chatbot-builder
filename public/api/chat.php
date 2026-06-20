<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../src/AdaptiveLocalChatbot.php';

$configFile = __DIR__ . '/../../config/chatbot.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'session_id' => 0,
        'answer' => 'Configuration missing. Copy config/chatbot.example.php to config/chatbot.php and update the database settings.',
        'tool' => 'config_missing',
        'confidence' => 0,
        'sources' => [],
        'links' => [],
        'cards' => [],
        'table_rows' => [],
        'calculations' => [],
        'suggestions' => [],
        'handoff' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once $configFile;

try {
    $payload = $_POST;
    if (($_SERVER['CONTENT_TYPE'] ?? '') !== '' && str_contains((string)$_SERVER['CONTENT_TYPE'], 'application/json')) {
        $json = json_decode((string)file_get_contents('php://input'), true);
        if (is_array($json)) {
            $payload = $json;
        }
    }

    $chatbot = new AdaptiveLocalChatbot(adaptive_chatbot_pdo(), adaptive_chatbot_config());
    echo json_encode($chatbot->handle($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'answer' => 'Chatbot error. Check server logs and configuration.',
        'tool' => 'server_error',
        'confidence' => 0,
        'sources' => [],
        'links' => [],
        'cards' => [],
        'table_rows' => [],
        'calculations' => [],
        'suggestions' => [],
        'handoff' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
