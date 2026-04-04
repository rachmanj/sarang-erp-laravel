# Action Plan: In-App HELP Assistant (Sarang ERP–scoped) + Bug / Feature Intake

## Problem statement

Users need **quick, trustworthy guidance** for **how to** use Sarang ERP (workflows, navigation) without leaving the app. Generic LLMs are unreliable for **exact menu paths and product-specific steps**. The product already has manuals under `docs/manuals/`; those should drive answers.

Additionally, users should be able to **report bugs** or **request improvements** in the same HELP area so feedback reaches the team without mixing it with “chat history” storage.

## Goals

1. Deliver a **HELP** experience that answers **only** Sarang ERP **how-to** questions, in **two languages** (Indonesian and English), with answers **grounded in curated content** (manuals + optional navigation index).
2. Keep **all application logic and knowledge preparation on the server**; **only** outbound calls to **OpenRouter** cross the boundary (for natural-language synthesis under strict rules).
3. **Do not** persist HELP **chat transcripts** for audit or analytics (no conversation logging).
4. Provide a separate, explicit path for **bug reports** and **feature requests** with **minimal, intentional persistence** (submissions only—not chat logs).

## Non-goals (current phase)

- Explaining arbitrary screen fields beyond what manuals cover (“how to” scope stays primary).
- Training or improving models; no fine-tuning.
- Storing or reviewing full chat history.
- Legal/tax advice as authoritative guidance (assistant may describe **how the app records** something if documented; otherwise defer).

## Guiding principles


| Principle                    | Implementation hint                                                                                                                 |
| ---------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| **Docs are source of truth** | Retrieval (RAG) from chunked manuals; LLM summarizes and cites sources.                                                             |
| **Bilingual parity**         | Ingest and chunk **both** ID and EN sources where available; or single doc with clear language sections—decide per file in Phase 0. |
| **Privacy**                  | OpenRouter API key in `.env` only; server-side calls; no key in frontend.                                                           |
| **No chat audit trail**      | API does not write messages to DB; optional in-memory rate limiting only.                                                           |
| **Feedback is intentional**  | Bug/feature submissions are a **separate** POST with structured fields; store or notify per Phase 3.                                |


---

## Phase 0 — Content inventory and bilingual strategy


| #   | Action                        | Details                                                                                                                                                                            |
| --- | ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0.1 | **Inventory manuals**         | List all `docs/manuals/*.md` (and any other approved help sources). Tag each chunk with `locale` (`id`, `en`) or `both` if one file contains both.                                 |
| 0.2 | **Gap analysis**              | Identify high-traffic flows (e.g. stock transfer, sales invoice, reports) missing EN or ID; prioritize translating or splitting files so retrieval can return the user’s language. |
| 0.3 | **Chunking rules**            | Split markdown by headings (`##` / `###`) with overlap; store metadata: `source_path`, `heading`, `locale`.                                                                        |
| 0.4 | **Optional navigation index** | Small YAML/JSON: module → menu path → route name (generated or maintained). Improves “where is Report menu?” without bloating the LLM context.                                     |


**Exit criteria:** Clear rule for which language the user gets; chunk pipeline defined; manuals cover priority flows in both languages or documented exceptions.

---

## Phase 1 — Server-side retrieval + OpenRouter integration


| #   | Action                    | Details                                                                                                                                                                            |
| --- | ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1.1 | **Embeddings storage**    | Store vectors locally (e.g. `help_embeddings` table: `chunk_id`, `locale`, `content`, `embedding`, `metadata` JSON) or file-based cache; refresh command after manual updates.     |
| 1.2 | **Embed + index command** | `php artisan help:reindex` (or similar): parse manuals, chunk, call embedding API (OpenRouter-compatible embedding model or separate provider—**still server-side only**).         |
| 1.3 | **Retrieval**             | On question: detect or accept `locale`; retrieve top-k chunks (+ optional nav index lines); build context window.                                                                  |
| 1.4 | **LLM call**              | Single OpenRouter chat completion: system prompt = scope (Sarang ERP only, how-to, cite sources, refuse off-topic, bilingual output matching user); user message + context chunks. |
| 1.5 | **Guardrails**            | If retrieval score below threshold: respond with short “not documented” + suggest manuals link or support channel; **do not** invent steps.                                        |
| 1.6 | **Rate limiting**         | Per-user or per-IP throttle on HELP endpoint to control cost and abuse (no persistent chat log).                                                                                   |


**Exit criteria:** End-to-end request returns a grounded answer from manuals; off-topic and empty-retrieval cases behave safely; secrets only in `.env`.

---

## Phase 2 — HTTP API (no chat persistence)


| #   | Action                | Details                                                                                                                           |
| --- | --------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| 2.1 | **Route**             | Authenticated route e.g. `POST /api/help/ask` or web middleware route (same session as app).                                      |
| 2.2 | **Request**           | `{ "message": "...", "locale": "id"                                                                                               |
| 2.3 | **Response**          | `{ "answer": "...", "sources": [{ "title", "path", "anchor?" }] }` — sources for UI “open manual” links.                          |
| 2.4 | **Explicit no-store** | Controller/service does not insert into `help_conversations` or equivalent; document this in code comment for future maintainers. |


**Exit criteria:** Frontend can render answer + sources; no DB writes for Q&A.

---

## Phase 3 — UI: HELP panel + bug / feature intake


| #   | Action                               | Details                                                                                                                                                  |
| --- | ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 3.1 | **Entry point**                      | Global HELP icon (header or floating) opening drawer/modal: chat-style **how-to** + language selector (ID / EN / follow UI).                             |
| 3.2 | **Suggested prompts**                | Optional chips: “How do I transfer stock?”, “Where are reports?”, etc., localized.                                                                       |
| 3.3 | **Bug / feature form**               | Separate tab or section: type (bug | feature), title, description, steps to reproduce (bugs), optional attachment policy (out of scope for v1 if heavy). |
| 3.4 | **Persistence for submissions only** | `help_feedback` table (or reuse existing ticket/issue pattern if any): `user_id`, `type`, `title`, `body`, `created_at` — **no** chat messages.          |
| 3.5 | **Notification**                     | Email or Slack/Teams webhook to dev team when new row inserted (config-driven).                                                                          |
| 3.6 | **Copy for users**                   | Short text: submissions are for triage; not a substitute for SLA or support contract.                                                                    |


**Exit criteria:** Users can ask how-to questions and submit bugs/features; submissions are stored and/or notified; Q&A still not logged.

---

## Phase 4 — Quality gates and rollout


| #   | Action                  | Details                                                                                                                                                         |
| --- | ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 4.1 | **Test matrix**         | Scripted questions in ID and EN for 5–10 critical flows; expected: correct menu/workflow or honest “not documented”.                                            |
| 4.2 | **Cost monitoring**     | Log **aggregate** token usage (optional metrics without message content) or periodic manual review of OpenRouter dashboard—**not** per-message content logging. |
| 4.3 | **Documentation**       | Short section in `docs/architecture.md` (diagram: Browser → Laravel → OpenRouter; manuals → embeddings).                                                        |
| 4.4 | **Operational runbook** | After manual changes: run `help:reindex`; verify key questions still retrieve correct chunks.                                                                   |


**Exit criteria:** Stakeholder sign-off on bilingual behavior and feedback flow; runbook exists.

---

## Implementation order (suggested)

1. Phase 0 (content + bilingual rules) — unblocks accurate RAG.
2. Phase 1.1–1.4 + Phase 2 (minimal API) — vertical slice.
3. Phase 1.5–1.6 + Phase 3 (UI + feedback table + notification).
4. Phase 4 (tests + architecture note + rollout).

---

## OpenRouter / configuration


| Variable (illustrative)      | Purpose                                                                         |
| ---------------------------- | ------------------------------------------------------------------------------- |
| `OPENROUTER_API_KEY`         | Server-side only.                                                               |
| `OPENROUTER_HELP_MODEL`      | Chat model for answers (choose after cost/quality trial).                       |
| `OPENROUTER_EMBEDDING_MODEL` | If embeddings go through OpenRouter; else separate embedding provider env vars. |


**Security:** Rotate keys if leaked; never commit `.env`; restrict production env access.

---

## Out of scope (this plan)

- Full **live** schema introspection or dynamic “current screen” context (can be a later enhancement).
- User-facing **chat history** restore across sessions.
- **Logging** full Q&A for audit (explicitly excluded).

---

## References

- Manuals: `docs/manuals/` (e.g. `warehouse-stock-transfer-manual-id.md`).
- Maintainer index: `docs/manuals/README.md`.
- Related conceptual discussion: scoped RAG + OpenRouter server-side; bilingual; bug/feature channel separate from chat storage.

---

## Shipped updates (2026-04-01)

| Area | Details |
| --- | --- |
| **Core** | `POST /help/ask`, `POST /help/feedback`; `help_embeddings`, `help_feedback`; `php artisan help:reindex`; OpenRouter client; throttling; no chat persistence. |
| **UI** | Navbar: **`fas fa-book-open`** in gradient circle (`head.blade.php` + `navbar.blade.php`). Modal: **no** `modal-dialog-scrollable`; `#help-answer` is sole scroll area (touch-friendly, `min-height: 0`); focus + `scrollTop` reset after reply (`help-panel.blade.php`). |
| **Knowledge** | `sales-invoice-manual-id.md` / `sales-invoice-manual-en.md`; `in-app-help-manual-id.md` / `in-app-help-manual-en.md`; **`domain-assistant-manual-id.md` / `domain-assistant-manual-en.md`** (Domain Assistant vs HELP; robot icon; privacy); `docs/manuals/README.md`; expanded `help-navigation.json` (incl. `domain-assistant`); `delivery-order-manual-id.md` cross-link to Sales Invoice manual. |
| **Docs** | `docs/architecture.md` (HELP section + mermaid), `docs/decisions.md`, `MEMORY.md` [097], `docs/todo.md`. |

**Operational reminder:** After changing any `docs/manuals/` or `help-navigation.json`, run **`php artisan help:reindex`** on each environment.

---

## Open decisions (to resolve during Phase 0–1)

1. **Embedding provider**: Same as OpenRouter vs dedicated embedding API (cost/latency/quality).
2. **Locale detection**: UI-only vs `Accept-Language` vs app `config('app.locale')`.
3. **Feedback workflow**: Email-only vs DB + email vs integration with external tracker (Jira, GitHub Issues).

