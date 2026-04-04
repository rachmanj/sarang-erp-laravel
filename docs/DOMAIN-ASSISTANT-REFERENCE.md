# Domain Assistant — implementation reference (portable)

This document describes how the **Domain Assistant** feature is built in the DDS Laravel application. Use it as a **blueprint** when implementing a similar “scoped AI chat + tools + audit” feature in **other Laravel (11+) projects**.

---

## 1. Goals

| Goal | Approach |
|------|----------|
| **Accurate domain answers** | The LLM must **not** invent SQL or raw tables; it calls **registered tools** that run **your** Eloquent queries with **your** authorization rules. |
| **Same visibility as the UI** | Tool implementations reuse (or mirror) list filters: **roles**, **locations**, **“show all records”** toggles, etc. |
| **Auditability** | Persist **per-request logs** (success/error, duration, tools invoked, optional IP/UA). |
| **Multi-session UX** | Support **multiple conversation threads** per user (not one implicit thread). |
| **Ops / governance** | Admin-only **report** over request logs with filters. |

---

## 2. Architecture overview

```
┌─────────────┐     POST /assistant/chat      ┌──────────────────────────┐
│   Browser   │ ────────────────────────────► │ DomainAssistantController │
└─────────────┘     JSON or SSE (stream)      └─────────────┬────────────┘
                                                            │
                    ┌───────────────────────────────────────┼───────────────────────┐
                    ▼                                       ▼                       ▼
         AssistantConversationManager              DomainAssistantService     AssistantRequestLog
         (session + DB threads)                    (OpenRouter + tool loop)   (audit row)

                    ▼
         DomainAssistantDataService
         (permission-scoped Eloquent; tool implementations)
```

- **OpenRouter** (or any OpenAI-compatible HTTP API) runs **chat completions** with **`tools`** (function calling).
- Each tool maps to a **method** on a dedicated **data service** class that returns **arrays** (serialized to JSON for the model).
- **Streaming**: optional SSE when tools are **off** and config allows streaming (project-specific safety choice).

---

## 3. Permissions and routes

- **Permission** (Spatie example): `access-domain-assistant` — assign via roles.
- **Route group**: `auth`, `active.user` (or your equivalents), `can:access-domain-assistant`.
- **Throttle** chat POST if needed (e.g. `throttle:30,1`).

**Typical routes**

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/assistant` | Chat UI |
| POST | `/assistant/chat` | Send message (JSON or SSE) |
| POST | `/assistant/clear` | Delete current conversation (project-defined behaviour) |
| GET | `/assistant/conversations` | List threads + active id |
| POST | `/assistant/conversations` | New thread |
| GET | `/assistant/conversations/{conversation}/messages` | Load messages |
| PATCH | `/assistant/conversations/{conversation}/select` | Set active thread in session |
| DELETE | `/assistant/conversations/{conversation}` | Delete thread |

**Scoped route model binding** (recommended): register a `Route::bind` for `{conversation}` so the resolved model is always `where('user_id', auth()->id())` → wrong id yields **404**, not 403 leakage.

---

## 4. Configuration (env + config)

Use a dedicated config block, e.g. `config/services.php` → `domain_assistant`:

- `enabled` — master switch.
- `openrouter` / provider: **API key**, **base URL**, **chat model**, **timeout**.
- `tools_enabled` — if `false`, you may allow **streaming** without tool loops (simpler, but no live DB tools).
- `streaming_enabled` — SSE path when tools off.
- `daily_user_message_limit` — optional `0` = unlimited.

**Never** commit API keys; document in `.env.example`.

---

## 5. Database

**Conversations**

- `assistant_conversations`: `user_id`, optional `title`, timestamps.
- `assistant_messages`: `assistant_conversation_id`, `role` (`user`/`assistant`), `content`, timestamps.

**Session key** for “active” conversation id, e.g. `domain_assistant.conversation_id`.

**Request logs**

- `assistant_request_logs`: `user_id`, `assistant_conversation_id`, `status` (`success`/`error`), `tools_invoked` (JSON array), `show_all_records` (bool), `user_message_length`, `duration_ms`, `error_summary`, `ip_address`, `user_agent`, timestamps.

Indexes: e.g. `(user_id, created_at)` on conversations; `(user_id, created_at)` or `(status, created_at)` on logs for admin reports.

---

## 6. Core services

### 6.1 Conversation manager

- **Resolve** active conversation: session id → load owned row; else create new and store in session.
- **Optional override**: `conversation_id` in chat request must be validated with `exists:assistant_conversations,id` **scoped to current user**.
- **Append exchange**: save user + assistant messages; set **title** on first user message (e.g. `Str::limit(trim($text), 80)`).
- **Clear / delete**: delete rows or only messages — project choice; keep session consistent.

### 6.2 Domain assistant service (LLM + tools)

- Build **messages**: system prompt (scope + rules) + history + new user message.
- Call provider API in a **loop** until no `tool_calls` or max iterations.
- **Execute tool** by name: map to data service methods; record tool names in `lastToolsInvoked` for logging.
- **System prompt** should explicitly say:
  - When the user names a **supplier/vendor**, pass the right tool arguments (e.g. `supplier_query` for invoices).
  - Do not invent IDs or rows.

### 6.3 Data service (tools)

Implement **one method per tool**, returning **arrays** (or `['error' => '...']`).

**DDS-specific patterns**

- **Invoice list scope**: reuse the same **location / role** rules as invoice index (`invoicesVisibleQuery`).
- **`search_invoices`**: parameters such as `status`, `limit` (cap e.g. 20), `date_from` / `date_to` (max window, e.g. 90 days), and **`supplier_query`** — filter with `whereHas('supplier', …)` on **name** and **vendor code**, with **LIKE** wildcards escaped for user input where appropriate.

**Other tools** (examples): `get_domain_summary`, `search_additional_documents`, `search_distributions`, `search_reconcile_records`, `search_suppliers`.

---

## 7. Tool definitions (OpenAPI-style for the provider)

Each tool needs:

- `name` (snake_case),
- `description` (what/when to use — **critical** for correct model behaviour),
- `parameters.properties` with types and short descriptions.

Register them in the chat completions payload in the format your provider expects (OpenAI-compatible: `tools: [{ type: 'function', function: { name, description, parameters } }]`).

---

## 8. Controller responsibilities

- Gate feature: `config('services.domain_assistant.enabled')` and API key present → else redirect or 503.
- **Daily limit**: count user messages since **start of day** across conversations → 429 if exceeded.
- **`show_all_records`**: only honour if user **can** `see-all-record-switch` (boolean).
- **Chat**: validate `message`, optional `conversation_id`, `stream`, `show_all_records`; resolve conversation; call service; persist messages; write `AssistantRequestLog`.
- **Stream**: same validation; stream tokens; on completion append exchange and log (same as non-stream).

---

## 9. UI (minimal expectations)

- Chat area + input; optional **terminal** styling.
- **Thread list**: load conversations on init; if empty, `POST` create one; switching thread = `PATCH` select + reload messages.
- Send `conversation_id` with each message.
- **CSRF** + `Accept: application/json` / SSE headers for fetch.

---

## 10. Admin report

- **Route**: e.g. `/admin/assistant-report`, middleware **`role:superadmin|admin`** (or your admin role names).
- **Query**: `AssistantRequestLog::query()->with(['user', 'conversation'])` + filters (user id, status, date range) + pagination.
- **View**: table with time, user, status, duration, tools, error snippet, IP.

---

## 11. Testing (suggested)

- Guest → redirect/login; user without permission → 403.
- Chat with **HTTP fake** on provider API → assert assistant message + DB messages + log row.
- **Threads**: create conversation, list, select; **other user** cannot access messages (404).
- **Admin report**: admin 200; non-admin 403.
- **Data service**: `search_invoices` with `supplier_query` returns only matching supplier’s invoices (feature test with seeded suppliers + invoices).

---

## 12. Porting checklist (another Laravel project)

1. [ ] Add permission + assign to roles.
2. [ ] Migrations: conversations, messages, request logs.
3. [ ] Config + `.env.example` entries.
4. [ ] `DomainAssistantService` + provider client (HTTP) + tool loop.
5. [ ] `DomainAssistantDataService` (or split by domain) — **every** query scoped to **auth rules**.
6. [ ] Controller + routes + optional `Route::bind` for conversation.
7. [ ] Blade UI + JS (threads + chat + optional stream).
8. [ ] `AssistantRequestLog` on success/failure.
9. [ ] Admin report controller + view + menu item.
10. [ ] Feature tests (auth, chat, threads, admin, supplier filter if applicable).
11. [ ] Document tool parameters in **decision log** and **architecture** doc.

---

## 13. DDS file map (this repository)

| Area | Path |
|------|------|
| Controller | `app/Http/Controllers/DomainAssistantController.php` |
| Services | `app/Services/DomainAssistantService.php`, `DomainAssistantDataService.php`, `AssistantConversationManager.php` |
| Models | `app/Models/AssistantConversation.php`, `AssistantMessage.php`, `AssistantRequestLog.php` |
| Request | `app/Http/Requests/AssistantChatRequest.php` |
| Route bind | `app/Providers/AppServiceProvider.php` (`conversation`) |
| Web routes | `routes/web.php` (`assistant` prefix) |
| Admin report | `app/Http/Controllers/Admin/AssistantReportController.php`, `routes/admin.php` |
| Views | `resources/views/assistant/index.blade.php`, `resources/views/admin/assistant-report/index.blade.php` |
| Lang | `lang/en/assistant.php` |

---

## 14. Related internal docs

- [`docs/architecture.md`](architecture.md) — Domain Assistant section (diagram + tables).
- [`docs/decisions.md`](decisions.md) — 2026-04-02 decision record.
- [`docs/todo.md`](todo.md) — Recently completed entry.

---

*Last updated: 2026-04-02 (DDS Laravel).*
