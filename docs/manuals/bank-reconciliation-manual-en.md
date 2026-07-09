# Bank Reconciliation — User Manual (English)

## Overview

**Bank Reconciliation** matches **bank statement lines** against **book (GL) lines** for a bank account and calendar month. It supports:

- **AI mode** — upload a PDF Rekening Koran; lines are parsed via OpenRouter (queued job).
- **Manual mode** — enter bank lines without a PDF.
- **N:M matching** — group one or many bank lines with one or many book lines when totals net to zero.
- **Exclude lines** — remove timing differences from the balance calculation.
- **Finalize** — complete when `bank_net + book_net ≈ 0` (excluding excluded lines).

---

## Where to find it

1. Sidebar **Accounting** → **Bank Accounts** — maintain bank master records linked to Chart of Accounts.
2. Sidebar **Accounting** → **Rekening Koran** — month grid (primary entry); **All Sessions** for the full list.

Permissions: `bank_accounts.view`, `bank_reconciliation.view`, `bank_reconciliation.import` (create sessions), `bank_reconciliation.reconcile` (match/exclude), `bank_reconciliation.finalize` (complete).

---

## Rekening Koran grid

The **Rekening Koran** page shows a matrix of **bank accounts × months** for the selected year.

- **Empty cell** — click to upload PDF or create a manual session for that month.
- **Colored badge** — session exists (`Processing`, `In Review`, `Completed`, `Failed`); click to open the workbench or report.
- Use **All Sessions** for a searchable list; use year arrows to change the calendar year.

---

## Create a reconciliation session

From the Koran grid (click an empty cell) or **New Session**:

1. Select **Bank Account** and **Period** (month).
2. Choose **AI** (PDF upload) or **Manual**.
3. Submit — AI parsing runs in the background (requires queue worker).

Only one session per bank account per month is allowed.

---

## Workbench

Open a session to see:

- **Sticky balance bar** — Bank Net, Book Net, Difference (must be ≈ 0 to finalize).
- **Bank lines** (left) and **Book lines** (right) — book lines are fetched from posted journals on the linked COA.
- **Match groups** — created by auto-match or manual multi-select.

### Actions

| Action | Description |
|--------|-------------|
| **Fetch Book Lines** | Re-load GL snapshot for the period (queued). |
| **Auto Match** | Run exact, fuzzy, and split matching (queued). |
| **Match Selected** | Select checkboxes on both sides; totals must net to zero. |
| **Exclude (X)** | Remove a line from balance (reason required). |
| **Unmatch** | Remove a match group and reset lines to unmatched. |
| **Finalize** | Mark session completed when balanced; opens printable report. |

### Sign convention

Statement **credit** (money in) is stored as bank `credit`; the matching book line is typically a **debit** on the bank GL account. Match validation uses `bank_total + book_total ≈ 0`.

---

## Tips

- Run `php artisan queue:work` in production so PDF parse and book fetch do not block the browser.
- Requires `OPENROUTER_API_KEY` for AI PDF parsing.
- Ensure Sales Receipts / Purchase Payments are **posted** before expecting them on the book side.
- Use **Exclude** for known timing differences instead of forcing a match.
