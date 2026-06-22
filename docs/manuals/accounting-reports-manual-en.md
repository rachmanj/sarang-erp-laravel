# Accounting reports — HELP reference (English)

Enhanced financial and subledger reports (2026-06). Menu: sidebar **Reports**. Permission: `reports.view`.

---

## Statement of Changes in Equity

**Reports** → **Changes in Equity**

Shows movements in equity accounts for a period — opening balance, net income tie-in, other changes, closing balance. Supports company entity and period filters. Export CSV/PDF where available.

Use after month-end close or for PSAK equity disclosure review.

---

## Subledger Reconciliation

**Reports** → **Subledger Reconciliation**

Compares **AR or AP aging totals** to the corresponding **GL control account** balance (`control_accounts` type `ar` / `ap`). Helps find subledger vs GL mismatches before closing.

Select as-of date and entity. Investigate differences via GL Detail or open invoice/receipt lists.

---

## PPN Reconciliation

**Reports** → **PPN Reconciliation**

Reconciles **output VAT (PPN Keluaran)** vs **input VAT (PPN Masukan)** from posted tax transactions and GL. Supports Indonesian SPT preparation; can export **SPT-1111** JSON where configured.

Cross-check with Tax Compliance dashboard and posted Sales/Purchase Invoices.

---

## Improved AR/AP balances and aging

**AR Party Balances**, **AP Party Balances**, **AR Aging**, and **AP Aging** now share the same **allocation-netted outstanding** basis (not raw invoice minus receipt sums).

Use the same as-of date on aging and balances for consistent figures.

---

## GL Detail and Cash Ledger

- **GL Detail** — journal lines by account with **running balance**; filter by period and entity.
- **Cash Ledger** — running balance for a **cash/bank** account (defaults to `1.1.1.x`, not AR).

Both respect posted-only default; optional include unposted where the UI allows.

---

## Balance Sheet, P&L, Cash Flow

Hierarchical display from COA `parent_id` with `report_group` / `normal_balance` classification. Balance Sheet includes unclosed P&L tie-out line. Cash Flow uses **indirect method** from `config/cash_flow.php` prefix mappings.

See `docs/financial-statements-reports.md` for technical detail.
