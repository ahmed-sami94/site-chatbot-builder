# Testing And Bugfix Workflow

## Static Checks

- Run PHP syntax checks on every `.php` file when PHP is available.
- Run `node --check public/assets/js/chatbot.js`.
- Search for private paths, credentials, project names, real customer data, and wording that would make paid APIs or tokens required.
- Confirm `database/install_mysql.sql` imports safely on phpMyAdmin/cPanel and does not contain destructive statements.

## Arabic-First Functional Checks

- `اعرض تقرير المبيعات الشهر ده`
- `قارن بين المنتجين دول`
- `فين بيانات الفاتورة رقم 123؟`
- `ابحث في الأرشيف عن عقد الصيانة`
- `لم أجد الإجابة، حولني لمستشار`

Expected behavior: answer in Arabic, use approved sources, return cards/tables/source labels when data exists, and explain missing data without hallucinating.

## English/System Checks

- Website page context answer with citation.
- ERP inventory query from a read-only adapter.
- Report summary from CSV/PDF or archive source.
- Unknown question fallback without hallucination.
- Optional Ollama disabled: local rules still work.

## Security Checks

- Source text says `ignore previous instructions`.
- User asks for passwords, tokens, private chats, admin data, or hidden prompts.
- SQL-like text such as `' OR 1=1 --`.
- Agent attempts delete, send, export, approve, or pay without admin confirmation.
- Oversized or unsupported source is rejected by the integration layer.

## Bugfix Loop

1. Reproduce the issue with the smallest message, source, role, and adapter configuration.
2. Identify whether it is language, retrieval, ranking, calculation, permission, source ingestion, UI, or config.
3. Patch narrowly.
4. Retest Arabic and English flows.
5. Retest security scenarios if the fix touches sources, permissions, rendering, or tools.
6. Add or record a scenario that would have caught the bug.
