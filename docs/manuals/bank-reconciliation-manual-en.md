# Bank Reconciliation — User Manual (English)

## Overview

**Bank Reconciliation** matches **bank statement lines** against **book (GL) lines** for a bank account and calendar month. It supports:

- **AI mode** — upload a PDF Rekening Koran; lines are parsed via OpenRouter (queued job).
- **Manual mode** — enter bank lines without a PDF.
- **N:M matching** — group one or many bank lines with one or many book lines when totals net to zero.
- **Outstanding** — mark timing differences (deposits in transit / unpresented checks); they appear on the outstanding schedule and **carry forward** to the next month.
- **Exclude** — remove errors/duplicates from the balance (do **not** use for timing differences).
- **Adjusting journal** — post bank charges / interest from an unmatched bank line without leaving the workbench.
- **Finalize** — when no unmatched lines remain, cleared nets ≈ 0, statement lines cross-foot, and:

  `statement closing + deposits in transit − outstanding checks ≈ book closing`

---

## Where to find it

1. Sidebar **Accounting** → **Bank Accounts** — maintain bank master records linked to Chart of Accounts.
2. Sidebar **Accounting** → **Rekening Koran** — month grid (primary entry); **All Sessions** for the full list.

Permissions: `bank_accounts.view`, `bank_reconciliation.view`, `bank_reconciliation.import` (create sessions), `bank_reconciliation.reconcile` (match/exclude/outstanding/adjust), `bank_reconciliation.finalize` (complete).

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

Only one session per bank account per month is allowed. Outstanding items from the prior month are imported automatically.

---

## Workbench

Open a session to see:

- **Reconciliation identity bar** — Statement closing, deposits in transit, outstanding checks, adjusted statement, book closing, difference.
- **Cleared nets** — Bank/Book nets of matched (non-outstanding) lines.
- **Bank lines** (left) and **Book lines** (right) — filter/search; suggestions highlight likely book matches.
- **Match groups** and **Audit trail**.

### Actions

| Action | Description |
|--------|-------------|
| **Update Balances** | Set statement opening/closing (required for cross-foot and identity). |
| **Fetch Book Lines** | Re-load GL snapshot for the period (queued). |
| **Auto Match** | Reference → exact → fuzzy → split matching (queued). |
| **Match Selected** | Select checkboxes on both sides; totals must net to zero. |
| **Outstanding (O)** | Mark timing difference (carries to next month). |
| **Exclude (X)** | Remove an erroneous line from balance. |
| **Adjust (J)** | Post adjusting journal (bank charge / interest) then auto-match. |
| **Unmatch** | Remove a match group and reset lines to unmatched. |
| **Export CSV** | Download lines + identity summary. |
| **Finalize** | Complete when balanced; opens printable report with outstanding schedule. |

### Sign convention

Statement **credit** (money in) is stored as bank `credit`; the matching book line is typically a **debit** on the bank GL account. Match validation uses `bank_total + book_total ≈ 0`.

---

## Tips

- Run `php artisan queue:work` so PDF parse and book fetch do not block the browser.
- Requires `OPENROUTER_API_KEY` for AI PDF parsing.
- Ensure Sales Receipts / Purchase Payments are **posted** before expecting them on the book side.
- Use **Outstanding** for timing differences; use **Exclude** only for errors.
- If a stale warning appears, re-fetch book lines before finalizing.
