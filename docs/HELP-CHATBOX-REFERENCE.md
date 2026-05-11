# In-app HELP chatbox (RAG) — implementation reference (portable)

This document describes how the **HELP** feature works in **Sarang ERP** (Laravel 12): a **chat-style modal** that answers “how do I …?” using **retrieval-augmented generation (RAG)** over Markdown manuals — **no live database queries**, **no tool calling**. Use it as a **blueprint** when building a similar feature in **another Laravel project**.

**Contrast**: The **Domain Assistant** (separate feature, robot icon) uses **function calling** into your ERP data. HELP (book / **?** icon) uses **only** indexed documentation. Do not merge the two unless you intentionally want a hybrid.

---

## 1. Goals and boundaries

| Aspect | HELP (this doc) | Typical “data assistant” |
|--------|------------------|---------------------------|
| **Knowledge source** | Markdown under e.g. `docs/manuals/`, plus optional `help-navigation.json` | Database + APIs (“tools”) |
| **Truth** | Whatever is in the manuals after **reindex** | Live queries (must match your permissions) |
| **Sessions** | Stateless: each question is independent | Often threaded / persisted |
| **Risk** | Hallucinated **UI steps** if context is thin | Wrong numbers if tools are wrong |

**Design intent**: The model receives **only** retrieved chunks as context; the system prompt instructs it **not** to invent menus, buttons, or field names when the context does not contain them.

---

## 2. Architecture overview

```
┌─────────────┐     POST /help/ask (JSON)     ┌─────────────────┐
│   Browser   │ ─────────────────────────────► │ HelpController   │
│  (modal UI) │                                 └────────┬────────┘
└─────────────┘                                          │
                  ┌────────────────────────────────────────▼────────────────────────┐
                  │ HelpAssistantService                                              │
                  │  1) embed user question (OpenRouter embeddings API)              │
                  │  2) load all rows from help_embeddings (or your vector store)   │
                  │  3) cosine similarity + locale boost → top-K chunks             │
                  │  4) if max score < threshold → "not documented"                  │
                  │  5) else chat completion with CONTEXT + question (OpenRouter)   │
                  └──────────────────────────────────────────────────────────────────┘
                                           │
                  ┌────────────────────────▼────────────────────────┐
                  │ Maintenance: php artisan help:reindex           │
                  │  HelpManualChunker → batches → embeddings → DB   │
                  └──────────────────────────────────────────────────┘
```

**Reference implementation paths (Sarang ERP)**:

| Piece | Location |
|-------|----------|
| HTTP | `HelpController` — `POST help.ask`, `POST help.feedback` |
| RAG orchestration | `App\Services\Help\HelpAssistantService` |
| OpenRouter HTTP | `App\Services\Help\HelpOpenRouterClient` (embeddings + chat) |
| Chunking | `App\Services\Help\HelpManualChunker` |
| Similarity | `App\Services\Help\HelpVector::cosineSimilarity` |
| Models | `HelpEmbedding`, `HelpFeedback` |
| Reindex command | `php artisan help:reindex` → `HelpReindexCommand` |
| UI | `resources/views/layouts/partials/help-panel.blade.php` (included from main layout) |
| Config | `config/help.php`, `config/services.php` → `openrouter`, `help_feedback` |

---

## 3. Data model

### 3.1 `help_embeddings`

Stores **one row per chunk** after reindex.

| Column | Purpose |
|--------|---------|
| `chunk_key` | Stable unique hash (e.g. SHA-256 of path + section index + heading) |
| `source_path` | Logical source, e.g. `docs/manuals/sales-invoice-manual-id.md` |
| `heading` | `##` section title or null |
| `locale` | `id`, `en`, `both`, etc. — used to **boost** retrieval for the user’s locale |
| `content` | Text passed to the LLM as part of CONTEXT |
| `embedding` | JSON array of floats (dimension depends on embedding model) |

**Porting tip**: At scale, move vectors to **pgvector**, **OpenSearch kNN**, or a hosted vector DB; keep the same **chunk metadata** (path, heading, locale, content).

### 3.2 `help_feedback` (optional but useful)

Stores **bug / feature** requests from the HELP modal (authenticated). Optional **email notify** via `HELP_FEEDBACK_NOTIFY_EMAIL`.

---

## 4. HTTP API contract

### 4.1 `POST /help/ask` (authenticated)

**Request (JSON)**:

```json
{
  "message": "How do I post a sales invoice?",
  "locale": "auto"
}
```

`locale`: `id` | `en` | `auto` (server maps `auto` from `app()->getLocale()`).

**Success response**:

```json
{
  "answer": "…HTML or markdown-safe text…",
  "sources": [
    { "title": "sales-invoice-manual-en.md", "path": "docs/manuals/...", "heading": "Posting" }
  ],
  "not_documented": false
}
```

**When nothing matches** (empty index, or best similarity below threshold):

```json
{
  "answer": "…user-facing message…",
  "sources": [],
  "not_documented": true
}
```

**Errors**: e.g. `503` if OpenRouter key missing or API failure (reference impl catches and reports).

**Throttling**: Apply `throttle` middleware (example: 30 requests/minute per user).

### 4.2 `POST /help/feedback` (authenticated)

**Request**: `type` (`bug` | `feature`), `title`, `body`, optional `steps_to_reproduce`.

**Response**: `201` with `{ "message": "ok", "id": … }`.

---

## 5. Retrieval logic (simplified)

1. **Embed** the user message with the same **embedding model** used at index time.
2. **Score** each stored chunk: **cosine similarity** between query vector and chunk vector.
3. **Locale boost** (example from reference impl): small additive bonus if chunk locale matches user locale (and smaller bonus for `both`).
4. **Sort** by score descending; take **top-K** (e.g. `HELP_TOP_K=6`).
5. If **best score** < **threshold** (e.g. `HELP_SIMILARITY_THRESHOLD=0.22`), return **not documented** without calling chat.
6. Else **concatenate** chunk bodies into a single `CONTEXT`, then call **chat completion** with a strict **system prompt**: answer only from CONTEXT; respond in user language; list sources.

**Tuning**:

- Lower threshold → more answers, more risk of weak context.
- Higher threshold → fewer answers, more “not documented”.
- Increase **top-K** → more context, higher token cost and noise.

---

## 6. Index pipeline (`help:reindex`)

1. **Chunk** all `*.md` in `docs/manuals/` (excluding configured skips):
   - Split on **`##` headings** — each section becomes one chunk.
   - Detect **locale** from filename heuristics (e.g. `*-manual-id.md` → `id`).
2. **Chunk** `help-navigation.json` into synthetic chunks (menu paths + keywords for “where is X?”).
3. **Truncate** `help_embeddings` (full rebuild; simple and deterministic).
4. For each **batch** of chunks:
   - Call **embeddings API** with chunk text (truncate per model limits, e.g. 30k chars).
   - **Insert** rows with vectors.

**Operational requirement**: After **any** manual edit that should affect answers, run **`php artisan help:reindex`** on that environment. CI can run it post-deploy if manuals are bundled.

**Resilience**: Reference client supports **timeouts**, **connect timeout**, and **retries** on embedding calls (configurable via env) to reduce `cURL error 28` in production.

---

## 7. Configuration (environment)

Typical variables (names may vary in your port):

| Variable | Purpose |
|----------|---------|
| `OPENROUTER_API_KEY` | Bearer token; **server-side only** — never expose to browser |
| `OPENROUTER_HELP_MODEL` / `OPENROUTER_CHAT_MODEL` | Chat model id on OpenRouter |
| `OPENROUTER_EMBEDDING_MODEL` | Embedding model id (must match reindex + query) |
| `OPENROUTER_HTTP_REFERER` / `APP_URL` | Some providers want `HTTP-Referer` |
| `OPENROUTER_TIMEOUT`, `OPENROUTER_CONNECT_TIMEOUT`, `OPENROUTER_EMBEDDING_RETRIES` | Network robustness |
| `HELP_SIMILARITY_THRESHOLD`, `HELP_TOP_K`, `HELP_REINDEX_BATCH_SIZE` | Retrieval + batching |
| `HELP_FEEDBACK_NOTIFY_EMAIL` | Optional plaintext email on feedback |

Chat and embeddings can instead point to **OpenAI**, **Azure OpenAI**, or self-hosted OpenAI-compatible endpoints — swap the HTTP client; keep the **same** embedding model for index and query.

---

## 8. Frontend UX (modal chatbox)

**Patterns from Sarang ERP**:

- **Launcher**: navbar control opens Bootstrap (or your) modal.
- **Tabs** (optional): “How-to” (ASK) vs “Report/request” (feedback form).
- **Scroll**: Use **one** scroll container for the answer (`#help-answer`). Do **not** combine `modal-dialog-scrollable` with a nested `overflow-y: auto` on the same content — **nested scroll** breaks mouse wheel / touch on many browsers.
- **Formatting**: Client-side formatter may turn model output into safe HTML (paragraphs, lists) and show **sources** under the answer.
- **POST** question via `fetch` / Axios to `help.ask` with CSRF and session cookie.

---

## 9. Security and compliance

- **Authenticate** `help.ask` and `help.feedback` (same as the rest of the app).
- **Throttle** to prevent abuse and cost spikes.
- **Never** send the API key to the browser; only server-side HTTP to the LLM provider.
- **PII**: Manuals should avoid secrets; feedback table may contain user text — apply your retention policy.
- **Content**: RAG reduces but does not eliminate **hallucination** — pair with “not documented” behavior and strict system prompts.

---

## 10. What not to put in HELP

- **Live KPIs**, document numbers, balances → use a **data assistant** with tools and authorization.
- **Legal/tax advice** — unless your manuals are reviewed by experts and scope is explicitly “how the app records …”.

---

## 11. Porting checklist

- [ ] Markdown **knowledge base** + authoring rules (`##` chunks, keywords).
- [ ] Optional **navigation JSON** for “where in the menu?” questions.
- [ ] Table(s) for chunks + vectors (or external vector index).
- [ ] **Reindex** command (chunk → embed → store).
- [ ] **HelpAssistantService**: embed question → retrieve → optional gate → chat.
- [ ] **Routes** + **auth** + **throttle**.
- [ ] **Modal UI** + accessible scroll + CSRF.
- [ ] **Tests**: e.g. `Http::fake` for provider; assert JSON shape and “not documented” path.
- [ ] **Runbook**: when to reindex; which env vars are required in production.

---

## 12. Related docs in this repository

- Maintainer authoring: `docs/manuals/README.md`
- In-app HELP vs Domain Assistant (product): `docs/manuals/in-app-help-manual-en.md`, `domain-assistant-manual-en.md`
- Architecture summary: `docs/architecture.md` (section **In-app HELP**)
- Similar blueprint for **live-data** assistant: `docs/DOMAIN-ASSISTANT-REFERENCE.md`

