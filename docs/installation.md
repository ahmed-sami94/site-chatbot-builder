# Installation

## Requirements

- PHP 8.0 or newer recommended.
- MySQL 5.7+/MariaDB with `utf8mb4`.
- PDO MySQL extension.
- mbstring is recommended for best Arabic handling; the core includes fallbacks when it is missing.
- No external AI API keys required.

## Steps

1. Copy `chatbot/` into your project.
2. Import `database/install_mysql.sql`.
3. Copy `config/chatbot.example.php` to `config/chatbot.php`.
4. Change the DSN/user/password via environment variables or your existing config system.
5. Configure table adapters in `adaptive_chatbot_config()`.
6. Add the widget snippet from `examples/php-mysql/embed-widget.php`.

## Production Notes

- Move `public/api/chat.php` behind your app's auth if the chatbot answers from internal ERP data.
- Keep public website chat limited to public tables and approved sources.
- Add rate limiting in your hosting/application layer.
- Keep `allow_ollama` disabled unless a local model server is intentionally configured.
- Keep `config/chatbot.php` out of Git; `.gitignore` already excludes it.
