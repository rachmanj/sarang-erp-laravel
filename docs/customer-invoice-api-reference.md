# Customer Invoice API — Reference

This document describes the **customer-facing REST API** for reading **sales invoices (AR)** and how **API keys** are issued from the admin UI.

## Concepts

| Term | Meaning |
|------|---------|
| Customer | A `BusinessPartner` with `partner_type = customer` (not `Master\Customer`). |
| Invoice | `sales_invoices` row linked by `business_partner_id`. |
| API key | Row in `customer_api_keys`; raw token is shown **once** when created; DB stores **SHA-256** hash only. |

## Base URL and prefix

Laravel registers API routes with the **`api` prefix** (see `bootstrap/app.php` / default `RouteServiceProvider` behavior).

| Pattern | Example |
|---------|---------|
| Base | `{APP_URL}/api` |
| This feature | `{APP_URL}/api/v1/...` |

Replace `{APP_URL}` with your environment URL (e.g. `https://erp.example.com`).

## Authentication

All endpoints below use middleware **`customer.api`** (`App\Http\Middleware\CustomerApiAuthentication`).

**Header (required):**

```http
Authorization: Bearer {plain_text_token}
```

**Behavior:**

1. Reads Bearer token; missing token → **401** `{ "message": "Unauthenticated." }`
2. Looks up `customer_api_keys.token` = `hash('sha256', plain_token)`
3. Unknown hash → **401** `{ "message": "Invalid API token." }`
4. `expires_at` in the past → **401** `{ "message": "API token expired." }`
5. Linked partner must exist, `partner_type === customer`, `status === active`; otherwise **401** `{ "message": "Invalid API token." }`
6. On success, updates `last_used_at` and attaches the partner as request attribute `api_business_partner`.

## Endpoints

### List invoices

**`GET /api/v1/invoices`**

Returns **only** invoices for the authenticated customer, ordered by `date` DESC, then `id` DESC.

**Query parameters (all optional)**

| Parameter | Type | Notes |
|-----------|------|--------|
| `status` | string | Filter `sales_invoices.status` (max 50 chars). |
| `date_from` | date | Inclusive lower bound on invoice `date`. |
| `date_to` | date | Inclusive upper bound on invoice `date`; must be ≥ `date_from` when both sent. |
| `per_page` | integer | Page size; clamped **1–100**, default **15**. |

**Pagination:** Standard Laravel paginator JSON (`data`, `links`, `meta`) wrapping `InvoiceResource` items.

**Example:**

```http
GET /api/v1/invoices?status=posted&date_from=2026-01-01&date_to=2026-12-31&per_page=25
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

### Single invoice (with lines)

**`GET /api/v1/invoices/{invoice_no}`**

**Route constraint:** `invoice_no` must match `[A-Za-z0-9._\-]+`.

Returns one invoice if it belongs to the authenticated customer; otherwise **404** (model not found).

Includes **`lines`** (all invoice lines) and **`currency`** when loaded.

**Example:**

```http
GET /api/v1/invoices/SI-2026-00042
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

## Response payloads (JSON resources)

Responses use Laravel API Resources with default **`data`** wrapping where applicable.

### Invoice (list item or detail)

Fields exposed by `App\Http\Resources\Api\Customer\InvoiceResource`:

| Field | Notes |
|-------|--------|
| `invoice_no` | string |
| `date` | `Y-m-d` or null |
| `due_date` | `Y-m-d` or null |
| `terms_days` | integer or null |
| `status` | string |
| `total_amount` | float; **amount due** (after header discount when present). |
| `discount_amount` | float; document-level discount in currency (`0` when none). |
| `discount_percentage` | float; header discount % (`0` when none). |
| `reference_no` | string or null |
| `description` | string or null |
| `posted_at` | ISO-8601 string or null |
| `exchange_rate` | float or null |
| `currency` | Object when relation loaded: `code`, `symbol`, `name`. List endpoint loads currency; omitted if not loaded. |
| `lines` | Array of line objects when `lines` relation loaded (**detail** endpoint only). |

### Invoice line

Fields exposed by `App\Http\Resources\Api\Customer\InvoiceLineResource`:

| Field | Notes |
|-------|--------|
| `item` | From `item_code` + `item_name`, or falls back to `description`. |
| `description` | string or null |
| `qty` | float |
| `unit_price` | float |
| `discount_amount` | float; **DPP (tax base) discount** on the line. |
| `discount_percentage` | float; line discount % (`0` when not used). |
| `discount` | float; same as **`discount_amount`** when that is set; otherwise a **legacy fallback** `max(0, qty×unit_price − total)` when the stored line total is less than gross DPP (older rows without explicit line discount). Prefer **`discount_amount`** for integrations. |
| `total` | Line gross (float): stored `amount` (net DPP + PPN − WTax for the line). |

## Admin: issuing and revoking API keys

UI entry: Business Partner **show** page → **API keys** (customers only), if the user has **`business_partners.manage`**.

Routes are under **`/admin`** and require:

- `auth`
- `permission:view-admin`
- `permission:business_partners.manage`

| Method | Path | Name |
|--------|------|------|
| GET | `/admin/customers/{businessPartner}/api-keys` | `admin.customers.api-keys.index` |
| POST | `/admin/customers/{businessPartner}/api-keys` | `admin.customers.api-keys.store` |
| DELETE | `/admin/customers/{businessPartner}/api-keys/{customerApiKey}` | `admin.customers.api-keys.destroy` |

**Create (POST body):**

| Field | Rules |
|-------|--------|
| `name` | Required, string, max 255 (human-readable label). |
| `expires_at` | Optional, date, must be **after today** if provided. |

After create, the **plain token** is flashed once to the session (`new_api_token`) and shown on the page — copy it immediately.

**Revoke:** DELETE removes the row; the token stops working on the next request.

## Database

**Table:** `customer_api_keys` (see migration `database/migrations/2026_05_11_141020_create_customer_api_keys_table.php`).

**Important:** Deployments must run migrations so this table exists:

```bash
php artisan migrate
```

## Related code (quick map)

| Piece | Location |
|-------|-----------|
| Routes | `routes/api.php` (`customer.api`, prefix `v1`) |
| Middleware alias | `bootstrap/app.php` → `customer.api` |
| Invoice controller | `app/Http/Controllers/Api/Customer/InvoiceController.php` |
| Resources | `app/Http/Resources/Api/Customer/` |
| API key model | `app/Models/CustomerApiKey.php` |
| Partner relation | `app/Models/BusinessPartner.php` → `apiKeys()` |
| Admin controller | `app/Http/Controllers/Admin/CustomerApiKeyController.php` |
| Admin form request | `app/Http/Requests/Admin/StoreCustomerApiKeyRequest.php` |
| Admin view | `resources/views/admin/customers/api-keys.blade.php` |
| Feature tests | `tests/Feature/CustomerInvoiceApiTest.php`, `tests/Feature/CustomerApiKeyAdminTest.php` |

## Related documentation

- **`docs/architecture.md`** — system architecture, validated functionality, API endpoint summary (`customer_api_keys`, `/api/v1/invoices`, admin key routes).
- **`docs/MODULES-AND-FEATURES.md`** — module list (Sales Invoices, Business Partners, API & Integration).
- **`docs/manuals/README.md`** — manuals index + developer/integration references table.

## Scope note

This API exposes **sales invoices only**. Purchase invoices, receipts, and other documents are **not** included.
