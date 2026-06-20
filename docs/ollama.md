# Optional Ollama Setup

Ollama is optional. The chatbot works without it.

## Enable

1. Install Ollama on a server you control.
2. Pull a model, for example:

```bash
ollama pull qwen3
```

3. Set config:

```php
'provider_mode' => 'ollama',
'allow_ollama' => true,
'ollama_base_url' => 'http://127.0.0.1:11434',
'ollama_model' => 'qwen3',
'ollama_temperature' => 0,
```

## Current Connector Behavior

- Uses Ollama `/api/chat`, not the older generate-only flow.
- Requests a small JSON object with `answer` and `confidence`.
- Keeps the deterministic local answer, cards, tables, calculations, and sources as the only grounding data.
- Uses temperature `0` by default for more deterministic structured responses.

## Safety

- Keep Ollama local or behind a private network.
- Do not expose it directly to the public internet.
- Use the local deterministic response as grounding.
- Do not let the model call write tools directly.
- If you enable Ollama tool calling elsewhere, keep tools read-only, schema-validated, allowlisted, and explicitly confirmed for any write action.
