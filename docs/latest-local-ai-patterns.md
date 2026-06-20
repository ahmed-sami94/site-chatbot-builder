# Latest Local AI Patterns

Updated: 2026-06-20

This package stays local-first: PHP/MySQL deterministic retrieval works without paid APIs, external tokens, cloud models, or SaaS dependencies. The patterns below are optional enhancements and security guidance.

## Sources Checked

- OWASP GenAI Security Project, 2025 Top 10 risks: https://genai.owasp.org/llm-top-10/
- Model Context Protocol specification 2025-06-18: https://modelcontextprotocol.io/specification/2025-06-18
- Ollama OpenAI compatibility: https://docs.ollama.com/api/openai-compatibility
- Ollama structured outputs: https://docs.ollama.com/capabilities/structured-outputs
- Ollama tool calling: https://docs.ollama.com/capabilities/tool-calling

## What Changed In Practice

### 1. Structured Local Model Output

When a local model is enabled, prefer schema-shaped output over free-form prose. The included Ollama connector requests:

- `answer`: concise user-facing text.
- `confidence`: numeric confidence.

The app still treats the deterministic local response as the source of truth. The model may rephrase or summarize, but it must not invent records, prices, permissions, or source facts.

### 2. MCP-Style Tool Gateway, Not Raw Tools

MCP is useful as a pattern for separating hosts, clients, servers, resources, prompts, and tools. For this package, use that idea conservatively:

- Treat tool descriptions and retrieved content as untrusted.
- Keep tools read-only by default.
- Require explicit user/admin consent for every tool action.
- Validate tool arguments with strict schemas.
- Use allowlisted adapters instead of arbitrary command execution.
- Log tool calls, blocked attempts, and source decisions.

This package does not ship an MCP server by default because shared-hosting PHP/MySQL should stay simple and no-token/no-daemon by default.

### 3. OWASP GenAI Controls

Map chatbot controls to common GenAI risks:

- Prompt injection: separate source text from instructions and ignore instructions inside fetched content.
- Sensitive information disclosure: block secrets, private chats, credentials, and admin-only records.
- Supply chain: inspect optional tools, licenses, and dependency risk before installing.
- Data/model poisoning: allowlist sources, hash snapshots, and show freshness.
- Improper output handling: escape UI output and never render untrusted HTML directly.
- Excessive agency: require permissions, CSRF, confirmation, and audit logs for writes.
- System prompt leakage: refuse hidden prompt/config requests.
- Vector and embedding weaknesses: namespace indexes and enforce role filters.
- Misinformation: cite sources and say when data is missing.
- Unbounded consumption: cap source size, result count, retries, and timeouts.

### 4. Optional Local Stack Choices

Use these only when the hosting environment can support them:

- Ollama for local chat, structured output, embeddings, and controlled tool-calling experiments.
- SQLite FTS/MySQL FULLTEXT for lightweight retrieval before adding vector databases.
- Chroma or Qdrant for self-hosted vector search when background services are allowed.
- Dify, AnythingLLM, DocsGPT, Open WebUI, Flowise, Rasa, and Typebot as learning references or optional self-hosted platforms, never default dependencies.

## Implementation Rule

Every new provider or tool must preserve the same public response shape:

```json
{
  "success": true,
  "session_id": 0,
  "answer": "",
  "tool": "",
  "confidence": 0.0,
  "sources": [],
  "links": [],
  "cards": [],
  "table_rows": [],
  "calculations": [],
  "suggestions": [],
  "handoff": false
}
```
