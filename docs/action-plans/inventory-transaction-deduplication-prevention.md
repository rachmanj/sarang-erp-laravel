# Action Plan: Prevent Duplicate Inventory Transactions (Purchase Invoice Post)

## Problem statement

Posting a purchase invoice creates `inventory_transactions` rows via `PurchaseInvoiceService::createInventoryTransaction()` → `InventoryService::processPurchaseTransaction()`. There is **no idempotency check** and **no database unique rule** that enforces one stock movement per PI line. Duplicates can appear from:

- Concurrent post requests (both see `draft` before either commits).
- Repeat execution of post logic without a safe “already processed” guard.

## Goals

1. Guarantee at most **one purchase inventory transaction per posted PI line** (for direct purchases that post stock).
2. Avoid blocking legitimate cases: **multiple lines on the same PI** that happen to use the **same inventory item** (two distinct lines must still produce two transactions).

## Guiding principle

Key stock movements by `purchase_invoice_line_id` (not only `purchase_invoice` + `item_id`), so uniqueness is per line, not per invoice+item.

---

## Phase 1 — Quick wins (low risk, no schema change)


| #   | Action                             | Details                                                                                                                                                                                                                                                                                                                                                                            |
| --- | ---------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1.1 | **Pessimistic lock on post**       | ✅ **Done (2026-03-31).** `PurchaseInvoiceController::post()` loads the invoice with `lockForUpdate()` inside `DB::transaction()` before status check and posting logic.                                                                                                                                                                                                               |
| 1.2 | **Idempotent check before insert** | ✅ **Done (2026-03-31).** `PurchaseInvoiceService::createInventoryTransaction()` returns existing purchase row when `purchase_invoice_line_id` already has a purchase transaction. |
| 1.3 | **UI: single-flight Post**         | ✅ **Done (2026-03-31).** `purchase_invoices/show.blade.php` — Post form disables submit on first submit and shows spinner + “Posting…”. |
| 1.4 | **Regression test**                | ✅ **Done (2026-03-31).** `tests/Feature/PurchaseInvoiceInventoryTransactionTest.php` — asserts `purchase_invoice_line_id`, single purchase row, second post does not add rows. |


**Exit criteria:** Concurrent double-post cannot create duplicate rows; normal post unchanged.

---

## Phase 2 — Schema: line-level reference (recommended)


| #   | Action                | Details                                                                                                                                                                                                                                                                                                                              |
| --- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 2.1 | **Migration**         | ✅ **Done (2026-03-31).** `2026_03_31_002238_add_purchase_invoice_line_id_to_inventory_transactions_table.php` — FK, backfill (match first unmatched tx per line by invoice + item), then unique index on `purchase_invoice_line_id`. |
| 2.2 | **Write path**        | ✅ **Done (2026-03-31).** `InventoryService::processPurchaseTransaction(..., ?int $purchaseInvoiceLineId)`; PI lines pass `purchase_invoice_line_id` on create. |
| 2.3 | **Unique constraint** | ✅ **Done (2026-03-31).** Unique on `purchase_invoice_line_id` (MySQL allows multiple NULLs for legacy rows). |
| 2.4 | **Unpost**            | Unpost still finds rows via `reference_type` / `reference_id` morph; line id is informational for new posts. Optional future: tie reversal rows to line id. |


**Exit criteria:** DB enforces one transaction row per PI line for purchases; duplicates impossible for new posts.

---

## Phase 3 — Hardening and operations


| #   | Action           | Details                                                                                                                                                                                                 |
| --- | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 3.1 | **Monitoring**   | ✅ **Done (2026-03-31).** `php artisan inventory:report-purchase-invoice-duplicates` — lists legacy duplicate PI+item groups and count of purchase rows missing `purchase_invoice_line_id`. |
| 3.2 | **Runbook**      | Use `inventory:fix-duplicate-transaction --item=... --dry-run` for legacy duplicates; use report command above for visibility. |
| 3.3 | **Decision log** | Updated in `docs/decisions.md`. |


---

## Implementation order (suggested)

1. Phase 1.1 + 1.3 (lock + UI) — fastest risk reduction.
2. Phase 2 (line id + unique) — structural fix.
3. Phase 1.2 refined to use `purchase_invoice_line_id` — clean idempotency.
4. Phase 1.4 + 3.1 + 3.2.

---

## Out of scope (for this plan)

- Duplicates originating from **other** document types (GR/PO, delivery, etc.) — apply the same pattern (line-level FK + unique where applicable) in separate tasks.
- Changing valuation methodology — only consistency of transaction rows is addressed here.

---

## References (code)

- `app/Http/Controllers/Accounting/PurchaseInvoiceController.php` — `post()`
- `app/Services/PurchaseInvoiceService.php` — `createInventoryTransaction()`
- `app/Services/InventoryService.php` — `processPurchaseTransaction()`
- `app/Console/Commands/FixDuplicateInventoryTransaction.php` — cleanup command
- `app/Console/Commands/ReportPurchaseInvoiceInventoryDuplicates.php` — report command
- `database/migrations/2026_03_31_002238_add_purchase_invoice_line_id_to_inventory_transactions_table.php`

---

## Document history


| Date       | Change              |
| ---------- | ------------------- |
| 2026-03-31 | Initial action plan |
| 2026-03-31 | Phases 1–3 implemented (lock, UI, line FK, unique, idempotency, test, report command) |


