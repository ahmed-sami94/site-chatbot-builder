# Adaptive Local Chatbot

Adaptive Local Chatbot is a free, open-source PHP/MySQL chatbot package for websites, ERP systems, reports, inventory tools, blogs, and database-backed platforms.

It works locally by default. No paid AI API, external token, SaaS chatbot, or cloud model is required. You can optionally connect it to Ollama or another local/self-hosted model later, while keeping the deterministic local engine as the fallback.

## What It Does

- Answers in Arabic or English from approved website, ERP, database, report, and file sources.
- Reads the current page or module context, such as product, report, invoice, article, or ERP screen.
- Supports local deterministic search, comparisons, calculations, report summaries, and safe fallback replies.
- Uses read-only adapters by default so public chat cannot write, delete, send, export, or change private data.
- Can be connected to Ollama as an optional local model provider.
- Stores sessions, messages, tool runs, approved sources, source snapshots, and pending confirmed actions.

## Fast Install

1. Copy this folder into your PHP project.
2. Import `database/install_mysql.sql` into your MySQL database.
3. Copy `config/chatbot.example.php` to `config/chatbot.php`, then update the PDO connection and table adapters.
4. Add this widget to your page:

```html
<link rel="stylesheet" href="/chatbot/public/assets/css/chatbot.css">
<div id="adaptiveLocalChatbot"></div>
<script src="/chatbot/public/assets/js/chatbot.js"></script>
<script>
  AdaptiveLocalChatbot.mount("#adaptiveLocalChatbot", {
    endpoint: "/chatbot/public/api/chat.php",
    language: "ar",
    context_type: "website",
    context_title: document.title
  });
</script>
```

## API

`POST public/api/chat.php`

Request fields:

- `message` required
- `session_id` optional
- `context_type` optional
- `context_id` optional
- `context_title` optional
- `language` optional: `ar`, `en`, or auto-detect

Response fields:

- `success`
- `session_id`
- `answer`
- `tool`
- `confidence`
- `sources`
- `links`
- `cards`
- `table_rows`
- `calculations`
- `suggestions`
- `handoff`

Health check:

```json
{"action": "health"}
```

History is intentionally disabled by default because numeric session IDs should not expose public transcripts. Enable `allow_public_history` only behind an authenticated app wrapper or an opaque session-token layer.

## Local First, Optional Ollama

The default `local_rules` mode uses PHP/MySQL search, adapters, and deterministic responses. To use Ollama, set provider mode to `ollama`, provide the local base URL such as `http://127.0.0.1:11434`, and choose a local model. If Ollama is not reachable, the local engine still works. The connector uses Ollama's `/api/chat` endpoint with JSON schema-shaped structured output and temperature `0` by default.

## Integration Files

- `config/chatbot.example.php`: copy to `config/chatbot.php`; never commit the real file.
- `examples/php-mysql/config.example.php`: drop-in example for projects that prefer to load config from their own include path.
- `examples/php-mysql/embed-widget.php`: minimal widget snippet.
- `public/api/chat.php`: JSON endpoint used by the widget.

## Security Model

- Data sources must be approved before use.
- External content is treated as untrusted data, never as instructions.
- Agents are read-only by default.
- Write/send/export actions must be routed through pending actions and confirmed by your app.
- SQL uses prepared statements in the included code.
- Public responses should expose only fields you allow in each adapter.

See `SECURITY.md` and `docs/source-security.md`.

Testing and bugfix workflow is documented in `tests/scenarios.md` and `docs/testing-and-bugfix.md`.

Current local AI and tool-gateway notes are in `docs/latest-local-ai-patterns.md`.

## Example Questions

Arabic:

- `اعرض تقرير المبيعات الشهر ده`
- `قارن بين المنتجين دول`
- `فين بيانات الفاتورة رقم 123؟`
- `لم أجد الإجابة، حولني لمستشار`

English:

- `Summarize this page`
- `Compare these two products`
- `Show this month's sales report`
- `Search the approved blog posts about maintenance`

## License

MIT License

Copyright (c) 2026 Ahmed sami

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
