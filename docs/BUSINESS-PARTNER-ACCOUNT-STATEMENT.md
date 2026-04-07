# Business Partner Detail — Account Statement (GL)

**Purpose**: Describe the **Account statement** tab on the Business Partner show page: behaviour, data sources, and how it differs from the **Transactions** tab and from the separate **Account Statements** module (`account_statements`).

**Last updated**: 2026-04-07  
**Status**: Current

## User-facing behaviour

### Where to find it

- **Path**: Business Partners → open a partner → tab **Account statement** (formerly labelled “Journal History” in older docs).
- **Legacy URL**: `GET /business-partners/{id}/journal-history` redirects to `GET /business-partners/{id}/account-statement` with query parameters preserved.

### In-app hint

The tab shows a one-line note:

> Shows posted GL activity on trade accounts; may differ from Transactions.

### Transactions tab vs Account statement tab

| | **Transactions** | **Account statement** |
|---|------------------|------------------------|
| **Intent** | Operational activity for the partner (orders, invoices, payments, etc., as implemented in the detail view). | **Posted general ledger** lines that match this partner’s trade/AP/AR picture. |
| **May include** | Documents that exist in trade modules, including drafts or items not yet reflected as expected GL rows. | Only **posted** journals whose lines hit the right **control or sub-accounts** and are linked to this partner (see Technical). |
| **Same rows?** | Not guaranteed. You can see activity on **Transactions** and an **empty** statement if nothing is posted to the scoped GL accounts for that period. |

### Date display and exports

- **On-screen** posting and document dates: **dd/mm/yyyy** (JavaScript formatter in `resources/views/business_partners/show.blade.php`).
- **CSV export**: same **d/m/Y** via `BusinessPartnerController::formatStatementCsvDate()`.
- **PDF export**: period header and line dates use **`d/m/Y`** in `resources/views/business_partners/pdf/account-statement.blade.php`.

### Export actions

- **CSV**: `GET /business-partners/{businessPartner}/account-statement/export?format=csv&start_date=…&end_date=…`
- **PDF**: `GET ...&format=pdf&…`  
  Route name: `business_partners.account_statement.export`.

---

## Technical implementation

### Service

- **`App\Services\BusinessPartnerAccountStatementService`**
  - Builds opening balance, period totals, and **one aggregated row per posted journal** (grouped by journal header fields), with running balance.
  - Resolves **document numbers** via `source_type` / `source_id` (e.g. purchase invoice, sales receipt, goods receipt PO).

### GL scope (why `business_partners.account_id` alone is not enough)

AP/AR posting often uses **central trade control accounts** (by **account `code`**), not only the partner’s optional `account_id`:

- **Supplier**: `2.1.1.01`, `2.1.1.03` (and same codes used in purchase posting flows).
- **Customer**: `1.1.2.01`, `1.1.2.04`.

The statement includes `journal_lines` on those accounts **when** the journal is tied to this partner via **`journals.source_type` / `source_id`** on the relevant document tables (e.g. `purchase_invoices`, `purchase_payments`, `goods_receipt_po`, `sales_invoices`, `sales_receipts`), **or** lines posted directly to **`business_partners.account_id`** when that sub-account is set.

### HTTP

- **JSON/AJAX** (tab): `BusinessPartnerController::accountStatement` — route name `business_partners.account_statement`.
- **Export**: `BusinessPartnerController::exportAccountStatement`.

### Tests

- **`tests/Feature/BusinessPartnerAccountStatementTest.php`**: JSON access, legacy redirect, CSV/PDF smoke, and statement rows when a journal is posted to the partner’s mapped account (see test setup).

---

## Relation to other documentation

- **Formal Account Statements module** (numbered `AST-…`, `account_statements` tables): see [`docs/ACCOUNT-STATEMENTS-IMPLEMENTATION.md`](./ACCOUNT-STATEMENTS-IMPLEMENTATION.md). That is a **different** feature from this **partner detail** GL statement.
- **Partner module architecture**: [`docs/business-partner-architecture.md`](./business-partner-architecture.md).

---

## Maintenance

- When posting flows change **control account codes** or **`source_type`** strings for journals, update **`BusinessPartnerAccountStatementService`** and this file.
- After meaningful edits to **Indonesian / English** user manuals under `docs/manuals/`, run **`php artisan help:reindex`** where in-app HELP is used (see `docs/manuals/README.md`).
