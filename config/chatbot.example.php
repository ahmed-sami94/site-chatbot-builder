<?php

declare(strict_types=1);

function adaptive_chatbot_pdo(): PDO
{
    $dsn = getenv('ADAPTIVE_CHATBOT_DSN') ?: 'mysql:host=127.0.0.1;dbname=your_database;charset=utf8mb4';
    $user = getenv('ADAPTIVE_CHATBOT_DB_USER') ?: 'your_user';
    $pass = getenv('ADAPTIVE_CHATBOT_DB_PASS') ?: 'your_password';

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function adaptive_chatbot_config(): array
{
    return [
        'version' => '0.2.0',
        'provider_mode' => 'local_rules',
        'allow_ollama' => false,
        'ollama_base_url' => 'http://127.0.0.1:11434',
        'ollama_model' => '',
        'ollama_temperature' => 0,
        'max_results' => 8,
        'allow_public_history' => false,
        'tables' => [
            [
                'table' => 'your_public_pages',
                'label' => 'Website pages',
                'id_field' => 'id',
                'label_field' => 'title',
                'search_fields' => ['title', 'body'],
                'url_pattern' => '/page.php?id={id}',
                'report' => false,
            ],
            [
                'table' => 'your_reports_view',
                'label' => 'Reports',
                'id_field' => 'id',
                'label_field' => 'report_title',
                'search_fields' => ['report_title', 'summary_text'],
                'url_pattern' => '/reports.php?id={id}',
                'report' => true,
            ],
        ],
    ];
}
