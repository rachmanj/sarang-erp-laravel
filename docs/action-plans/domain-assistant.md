# Action Plan: Domain Assistant (Sarang ERP)

## What This Is

A **Domain Assistant** — an LLM-powered chat that calls **live ERP data tools** (function calling), maintains **multi-session conversation threads**, and provides an **admin audit report**. Architecturally distinct from the existing HELP system (which is RAG-only, stateless, no DB access).

## What Already Exists (Do Not Duplicate)

| Area | File |
|---|---|
| OpenRouter HTTP client (embeddings + basic chat) | `app/Services/Help/HelpOpenRouterClient.php` |
| Help config | `config/help.php` |
| Help models | `app/Models/HelpEmbedding.php`, `HelpFeedback.php` |
| Help UI (modal, RAG) | `resources/views/layouts/help-panel.blade.php` |

The Domain Assistant gets its own stack alongside the Help system — not on top of it.

---

## Phase 1 — Infrastructure

### 1.1 Config

Add `domain_assistant` block to `config/services.php`:

```php
'domain_assistant' => [
    'enabled'      => env('DOMAIN_ASSISTANT_ENABLED', false),
    'model'        => env('DOMAIN_ASSISTANT_MODEL', 'anthropic/claude-3.5-sonnet'),
    'tools_enabled'    => env('DOMAIN_ASSISTANT_TOOLS_ENABLED', true),
    'daily_user_limit' => env('DOMAIN_ASSISTANT_DAILY_LIMIT', 50), // 0 = unlimited
],
```

Add to `.env.example`:

```
DOMAIN_ASSISTANT_ENABLED=false
DOMAIN_ASSISTANT_MODEL=anthropic/claude-3.5-sonnet
DOMAIN_ASSISTANT_TOOLS_ENABLED=true
DOMAIN_ASSISTANT_DAILY_LIMIT=50
```

### 1.2 Permission

Add Spatie permission `access-domain-assistant`. Assign to `superadmin` and `admin` roles initially (configurable via admin role management).

### 1.3 Migrations

Three new tables:

**`assistant_conversations`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `user_id` | foreignId → users | cascade delete |
| `title` | string(120), nullable | set from first user message |
| timestamps | | |

Index: `(user_id, created_at)`.

**`assistant_messages`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `assistant_conversation_id` | foreignId | cascade delete |
| `role` | enum: user, assistant | |
| `content` | text | |
| timestamps | | |

**`assistant_request_logs`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `user_id` | foreignId → users | nullOnDelete |
| `assistant_conversation_id` | foreignId, nullable | nullOnDelete |
| `status` | enum: success, error | |
| `tools_invoked` | json, nullable | array of tool names called |
| `duration_ms` | unsignedInteger, nullable | |
| `error_summary` | string(500), nullable | |
| `ip_address` | string(45), nullable | |
| `user_agent` | string(500), nullable | |
| timestamps | | |

Index: `(status, created_at)`, `(user_id, created_at)`.

### 1.4 Models

- `app/Models/AssistantConversation.php` — `belongsTo(User)`, `hasMany(AssistantMessage)`, `hasMany(AssistantRequestLog)`
- `app/Models/AssistantMessage.php` — `belongsTo(AssistantConversation)`
- `app/Models/AssistantRequestLog.php` — `belongsTo(User)`, `belongsTo(AssistantConversation)`, cast `tools_invoked` → array

---

## Phase 2 — Core Services

### 2.1 `AssistantConversationManager`

`app/Services/Assistant/AssistantConversationManager.php`

Responsibilities:
- **Resolve** active conversation: session key `domain_assistant.conversation_id` → load row owned by `auth()->id()`; if missing, create new thread and store id in session
- **Override**: if `conversation_id` passed in request, validate it belongs to current user (else 404)
- **Append exchange**: save user message → assistant message; set `title` on first message (`Str::limit(trim($text), 80)`)
- **Delete thread**: delete conversation row (cascades messages); clear session key
- **List**: `AssistantConversation::where('user_id', auth()->id())->orderByDesc('updated_at')`

### 2.2 `DomainAssistantOpenRouterClient`

`app/Services/Assistant/DomainAssistantOpenRouterClient.php`

Extends the pattern of `HelpOpenRouterClient` but adds function-calling support:

```php
public function chatCompletionWithTools(
    string $model,
    array $messages,   // [{role, content}]
    array $tools,      // OpenAI-format tool definitions
    float $temperature = 0.2,
): array               // full choice object (message + tool_calls)
```

Uses the same `Authorization: Bearer` + `HTTP-Referer` headers. Reads `DOMAIN_ASSISTANT_MODEL` / timeout from config.

### 2.3 `DomainAssistantService`

`app/Services/Assistant/DomainAssistantService.php`

The LLM + tool loop:

1. Build `messages` array: system prompt + history (last N turns) + new user message
2. `POST /chat/completions` with tools array
3. If `tool_calls` in response: execute each tool → append `tool` role messages → loop (max 5 iterations to prevent runaway)
4. Return `['answer' => string, 'tools_invoked' => string[]]`

**System prompt must:**
- State: you are an ERP assistant for Sarang ERP; answer only questions about this company's data
- Instruct: always call a tool to fetch data before answering; never invent document numbers, IDs, or amounts
- Instruct: if no tool covers the question, say so and suggest contacting support

### 2.4 `DomainAssistantDataService`

`app/Services/Assistant/DomainAssistantDataService.php`

One method per tool. Every query scoped to current user's visibility (mirroring existing controller index queries).

**Initial tool set:**

| Tool name | Method | Source model | Key params |
|---|---|---|---|
| `get_erp_summary` | `getErpSummary()` | SO/PO/DO/GRPO counts | *(none)* |
| `search_sales_orders` | `searchSalesOrders()` | `SalesOrder` | `customer_query`, `status`, `date_from`, `date_to`, `limit` ≤ 20 |
| `search_purchase_orders` | `searchPurchaseOrders()` | `PurchaseOrder` | `supplier_query`, `status`, `date_from`, `date_to`, `limit` |
| `search_delivery_orders` | `searchDeliveryOrders()` | `DeliveryOrder` | `customer_query`, `status`, `date_from`, `date_to`, `limit` |
| `search_goods_receipt_po` | `searchGoodsReceiptPO()` | `GoodsReceiptPO` | `supplier_query`, `status`, `date_from`, `date_to`, `limit` |
| `search_inventory_items` | `searchInventoryItems()` | `InventoryItem` | `name_query`, `category`, `warehouse_id`, `low_stock_only`, `limit` |
| `search_business_partners` | `searchBusinessPartners()` | `BusinessPartner` | `name_query`, `type` (customer/supplier/both), `limit` |

Each method returns `array` (or `['error' => '...']` on failure). Dates capped at 90-day window. `limit` capped at 20.

---

## Phase 3 — HTTP Layer

### 3.1 Form Request

`app/Http/Requests/AssistantChatRequest.php`

```
message          required|string|max:4000
conversation_id  nullable|exists:assistant_conversations,id (+ scoped to user in controller)
show_all_records nullable|boolean
```

### 3.2 Controller

`app/Http/Controllers/DomainAssistantController.php`

Guard checks (in order):
1. Feature enabled + API key present → else redirect with error
2. `can('access-domain-assistant')` → else 403
3. **Daily limit**: count `assistant_messages` where `role=user` for current user today (across all conversations) → 429 if ≥ `daily_user_limit`
4. `show_all_records` only honoured if user `can('see-all-record-switch')`

After successful LLM response: persist messages via `AssistantConversationManager`, write `AssistantRequestLog` (status=success).
On exception: write `AssistantRequestLog` (status=error, error_summary), return 503.

### 3.3 Routes

`routes/web.php` — group: `auth`, `active.user`, `can:access-domain-assistant`, throttle `60,1`

| Method | URI | Controller method |
|---|---|---|
| GET | `/assistant` | `index` |
| POST | `/assistant/chat` | `chat` |
| GET | `/assistant/conversations` | `listConversations` |
| POST | `/assistant/conversations` | `createConversation` |
| GET | `/assistant/conversations/{conversation}/messages` | `loadMessages` |
| PATCH | `/assistant/conversations/{conversation}/select` | `selectConversation` |
| DELETE | `/assistant/conversations/{conversation}` | `deleteConversation` |

### 3.4 Route Bind

In `AppServiceProvider::boot()`:

```php
Route::bind('conversation', function (string $value) {
    return \App\Models\AssistantConversation::where('user_id', auth()->id())
        ->findOrFail($value);
});
```

Wrong user → 404, not 403 (prevents ID enumeration).

---

## Phase 4 — Blade UI

`resources/views/assistant/index.blade.php`

Full-page layout (not a modal). The Domain Assistant uses a **hacker / terminal aesthetic** — dark background, monospace font, green-on-black palette — applied **only to this page**, leaving the rest of the app untouched.

### Visual Theme

| Property | Value |
|---|---|
| Background | `#0d1117` (near-black) |
| Primary text | `#00ff41` (matrix green) |
| Dim text / labels | `#4a7c59` |
| User message accent | `#00d4ff` (cyan) |
| Assistant accent | `#00ff41` (green) |
| Tool-call trace | `#ff9500` (amber) |
| Error | `#ff453a` (red) |
| Font | `'JetBrains Mono', 'Fira Code', 'Courier New', monospace` — loaded via Google Fonts |
| Border style | `1px solid #1a3a2a` with subtle green glow on focus |
| Scanlines | CSS `::after` pseudo-element with repeating-linear-gradient — 2px stripes at 4% opacity |

### Layout

Two panels inside `@extends('layouts.main')` — the AdminLTE chrome (navbar, sidebar) stays as-is; the terminal skin lives only inside the content area.

```
┌─────────────────────────────────────────────────────────┐
│  THREAD LIST  col-md-3                                  │
│  ─────────────────────────────────────────────────────  │
│  SARANG-ERP ASSISTANT v1.0                              │
│  ─────────────────────────────────────────────────────  │
│  [+] NEW SESSION                                        │
│                                                         │
│  > [ACTIVE] Sales order PT. Sinar Jaya_                 │
│    2026-04-04 14:32                                     │
│                                                         │
│    Purchase orders March 2026                           │
│    2026-04-03 09:11                                     │
│                                                         │
│    Inventory low stock check                            │
│    2026-04-02 16:45                        [×]          │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  CHAT AREA  col-md-9                                    │
│  ─────────────────────────────────────────────────────  │
│                                                         │
│  [USER@sarang ~]$ show me open sales orders for         │
│                   PT. Sinar Jaya                        │
│                                                         │
│  > executing tool: search_sales_orders ...              │  ← amber tool trace
│  > parameters: { customer_query: "PT. Sinar Jaya",     │
│                  status: "open", limit: 20 }            │
│  > 3 records returned.                                  │
│                                                         │
│  [ASSISTANT]──────────────────────────────────────────  │
│  Found 3 open Sales Orders for PT. Sinar Jaya:          │
│                                                         │
│    SO-2026-0041   Rp  45,000,000   Draft                │
│    SO-2026-0038   Rp  12,500,000   Confirmed            │
│    SO-2026-0031   Rp   8,200,000   Confirmed            │
│                                                         │
│  ────────────────────────────────────────────────────── │
│                                                         │
│  > _  (blinking cursor while loading)                   │
│                                                         │
│  ─────────────────────────────────────────────────────  │
│  [sarang-erp:assistant]> _________________________ [▶]  │  ← input bar
│  shift+enter for newline · enter to send                │
└─────────────────────────────────────────────────────────┘
```

### Key UI Elements

**Thread list panel:**
- Header: `SARANG-ERP ASSISTANT v1.0` in dim green — decorative, no interaction
- Active thread prefixed with `> ` and trailing `_` cursor blink
- Inactive threads indented, no prefix, dim color — brighten on hover
- Delete button `[×]` appears on hover only (right side)
- "New session" button styled as `[+] NEW SESSION` — monospace bordered button

**User messages:**
- Formatted as a terminal prompt: `[USER@sarang ~]$` prefix in cyan, then the message text
- Right-aligned in a dark card with cyan left border

**Tool call traces** (shown between user message and assistant reply):
- Amber `#ff9500` text, smaller font size
- Each tool call rendered as:
  ```
  > executing tool: search_sales_orders ...
  > parameters: { customer_query: "...", status: "open" }
  > N records returned.
  ```
- Collapsible by clicking — collapsed shows just `> [tool_name] ✓` to save space after reading

**Assistant messages:**
- Header line: `[ASSISTANT]──────` (green em-dash line fills width)
- Body text in matrix green on dark background
- Light markdown rendering: bold, bullet lists, numbered lists (same `formatBlock()` pattern as HELP panel)
- Footer separator line: `──────────────────`

**Loading / thinking state:**
- Animated text replacing the assistant bubble:
  ```
  > connecting to ERP...
  > querying database...   ← cycles through these with 700ms interval
  > processing...
  ```
- Single blinking `_` cursor in the message stream

**Input bar:**
- Prompt prefix `[sarang-erp:assistant]>` as non-editable label
- `<textarea>` with `rows="2"` — dark background, green text, green caret
- Send button: `[▶ EXECUTE]` — monospace, bordered, no Bootstrap `.btn-primary` rounded style
- Subtle green glow on focus: `box-shadow: 0 0 0 2px rgba(0,255,65,0.25)`

**Show all records toggle** (if permitted):
- Styled as: `[ALL BRANCHES: OFF]` / `[ALL BRANCHES: ON]` — toggleable text button in the header bar, no Bootstrap switch

### CSS Strategy

- All terminal styles scoped inside `.assistant-terminal { }` wrapper class on the page root `<div>`
- Does **not** affect AdminLTE sidebar, navbar, or any other page
- Loads `JetBrains Mono` from Google Fonts via a `@push('css')` stack only on this view
- Scanline overlay is a non-interactive `::before` pseudo-element on `.assistant-terminal`

### JS Behavior

- On load: `GET /assistant/conversations` → render thread list; load active thread messages
- Send: `POST /assistant/chat` with `{message, conversation_id, show_all_records}` → append user bubble → show loading animation → append tool traces + assistant reply
- Switch thread: `PATCH /assistant/conversations/{id}/select` → update active highlight → load messages
- Delete thread: `DELETE /assistant/conversations/{id}` → remove from list → select next or auto-create
- New thread: `POST /assistant/conversations` → prepend to list → clear chat area
- All vanilla JS (no framework) — consistent with existing HELP panel implementation

### Navbar Entry Point

Add to `navbar.blade.php` alongside the existing HELP icon, permission-gated:

```blade
@can('access-domain-assistant')
<li class="nav-item">
    <a class="nav-link py-1" href="{{ route('assistant.index') }}" title="Domain Assistant">
        <span class="help-nav-icon-wrap"><i class="fas fa-robot" aria-hidden="true"></i></span>
    </a>
</li>
@endcan
```

Icon: `fa-robot` — clearly distinct from `fa-book-open` (HELP). Same icon wrapper style as HELP for visual consistency in the navbar.

---

## Phase 5 — Admin Report

`app/Http/Controllers/Admin/AssistantReportController.php`

Middleware: `role:superadmin|admin`

Query `AssistantRequestLog::with(['user', 'conversation'])` + filters:
- User (name/email search)
- Status (success / error)
- Date range

Paginated table: timestamp, user, conversation title, status, duration (ms), tools called (comma-list), error snippet, IP address.

View: `resources/views/admin/assistant-report/index.blade.php`

Route in admin routes: `GET /admin/assistant-report` → add to admin sidebar menu.

---

## Phase 6 — Docs & Tests

- Update `docs/architecture.md`: Domain Assistant section with Mermaid diagram
- Log in `docs/decisions.md`: model selection, tool scope, `show_all_records` propagation rule
- Feature tests:
  - Guest → redirect; user without permission → 403
  - Chat flow (HTTP fake OpenRouter) → assert assistant message + DB rows + log row
  - Thread isolation: another user's `conversation_id` → 404
  - Daily limit: exceed → 429
  - Admin report: admin → 200; non-admin → 403
  - `search_sales_orders` with `customer_query` returns only matching customer's orders

---

## File Map

| Area | Path |
|---|---|
| Config | `config/services.php` (domain_assistant block) |
| Migrations | `database/migrations/..._create_assistant_conversations_table.php` etc. |
| Models | `app/Models/AssistantConversation.php`, `AssistantMessage.php`, `AssistantRequestLog.php` |
| Services | `app/Services/Assistant/AssistantConversationManager.php` |
| | `app/Services/Assistant/DomainAssistantOpenRouterClient.php` |
| | `app/Services/Assistant/DomainAssistantService.php` |
| | `app/Services/Assistant/DomainAssistantDataService.php` |
| Form Request | `app/Http/Requests/AssistantChatRequest.php` |
| Controller | `app/Http/Controllers/DomainAssistantController.php` |
| Admin Controller | `app/Http/Controllers/Admin/AssistantReportController.php` |
| Views | `resources/views/assistant/index.blade.php` |
| | `resources/views/admin/assistant-report/index.blade.php` |
| Route bind | `app/Providers/AppServiceProvider.php` |
| Routes | `routes/web.php` |

---

## Open Decisions (Resolve Before Starting)

| # | Question | Options |
|---|---|---|
| 1 | **LLM model** | `anthropic/claude-3.5-sonnet` vs `openai/gpt-4o` — confirm cost/quality preference |
| 2 | **`show_all_records` propagation** | Should Domain Assistant respect branch/location visibility toggle? Recommend: yes, mirror UI rules |
| 3 | **Daily message limit** | Default 50 suggested; confirm with team based on OpenRouter cost estimate |
| 4 | **Tool expansion scope (Phase 1)** | Start with the 7 tools listed; `search_grgi`, `search_assets`, `search_journals` can be Phase 2 |

---

## Build Order

1. Phase 1 — migrations, models, config, permission (pure scaffolding, zero risk to live data)
2. Phase 2.1 + 2.2 — conversation manager + OpenRouter client
3. Phase 2.3 — LLM tool loop (smoke-test with 1 dummy tool)
4. Phase 2.4 — data service tools (start with `get_erp_summary` + `search_sales_orders`, expand incrementally)
5. Phase 3 — controller + routes + route bind
6. Phase 4 — UI
7. Phase 5 — admin report
8. Phase 6 — docs + tests

---

*Created: 2026-04-04. Status: Implemented (2026-04-04).*

**Shipped same day (iterations):** Tools `search_sales_invoices` and `get_sales_invoice_detail` (header + `sales_invoice_lines`); **`search_purchase_invoices`** and **`get_purchase_invoice_detail`** (header + `purchase_invoice_lines`) for **faktur pembelian** / AP. Invoice **number** lookup uses `scopeActiveCompanyEntities` so invoices on non-default active entities are visible; list/browse without a number still uses default entity + `see-all-record-switch` / `show_all_records` as elsewhere. Prompt and tool descriptions steer “invoice / faktur” to AR vs SO and **faktur pembelian** to PI vs PO. Navbar launcher **`fas fa-robot`**. HELP: `domain-assistant-manual-en.md` / `domain-assistant-manual-id.md`, `help-navigation.json` entry `domain-assistant` — run **`php artisan help:reindex`** after manual changes.

After deploy: run `php artisan db:seed --class=RolePermissionSeeder` (or grant `access-domain-assistant` in admin) and set `.env` (`DOMAIN_ASSISTANT_ENABLED=true`, `OPENROUTER_API_KEY`).
