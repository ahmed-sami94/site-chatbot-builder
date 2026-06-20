# Source Security

Sources are data, not instructions.

## Allowed Sources

- Approved database views.
- Approved website pages.
- Approved reports.
- Uploaded files that your app parses locally.
- Optional local model responses grounded in retrieved data.

## Required Protections

- Use allowlists for domains and internal APIs.
- Block localhost/private-IP fetches if you add internet ingestion.
- Enforce file size and content-type limits.
- Escape all output.
- Keep source citations.
- Store hashes and freshness dates for crawled snapshots.
- Never execute scripts, macros, or document actions.
- Treat MCP/tool descriptors, adapter metadata, and remote tool descriptions as untrusted unless they come from a reviewed, allowlisted server.
- Require explicit consent, schema validation, and audit logs for any tool-style operation.

## Prompt Injection Example

If a fetched page says `ignore previous instructions`, the chatbot should treat that sentence as page content only. It must not change security rules, hidden instructions, adapter permissions, or system behavior.
