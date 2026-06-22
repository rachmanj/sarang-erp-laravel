# Tax Compliance (Indonesia) — User Manual (English)

## Overview

The **Tax Compliance** module tracks PPN (VAT), withholding (PPh), tax periods, compliance logs, and export helpers for Indonesian reporting (Coretax, e-Bupot, SPT). Tax transactions sync automatically when Purchase Invoices and Sales Invoices are **posted**.

Menu: **Accounting** → **Tax Compliance**. Permission: `tax.view` (and `tax.update`, `tax.approve` for workflows).

---

## Dashboard

The Tax Compliance **dashboard** shows the current tax period summary: transaction counts, taxable amounts, PPN net balance, and quick links to transactions, reports, calendar, and compliance logs.

---

## Tax transactions

Posted invoices create or update **tax_transactions** rows with scaled DPP, PPN, and withholding amounts. View list: Tax Compliance → **Transactions**.

Purchase invoice lines support **wtax_rate** (withholding). Sales invoices support **Faktur Pajak** fields for e-Faktur export.

---

## Reports and exports

- **PPN Reconciliation** (under Reports menu) — GL vs tax ledger tie-out; SPT-1111 JSON export.
- Tax module **Reports** — create/submit/approve periodic tax reports.
- **Compliance logs** — audit trail of tax actions.
- **Calendar** — due dates for tax obligations.

Exports include **Coretax** and **e-Bupot** CSV formats where configured.

---

## Year-end and periods

Accounting **Periods** (Accounting → Periods) supports monthly close/open and **Close Fiscal Year** (posts year-end journal via `YearEndClosingService`). Document posting and deletion are blocked in closed periods.

Tax period management is separate under Tax Compliance → Periods but should align with GL period close for consistent reporting.

---

## Permissions summary

| Action | Permission |
|--------|------------|
| View dashboard & transactions | `tax.view` |
| Create/edit transactions | `tax.create`, `tax.update` |
| Submit reports | `tax.update` |
| Approve reports | `tax.approve` |

After manual or doc changes, administrators run `php artisan help:reindex` so HELP answers stay current.
