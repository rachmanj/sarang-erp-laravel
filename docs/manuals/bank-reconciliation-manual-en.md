# Bank Reconciliation — User Manual (English)

## Overview

**Bank Reconciliation** matches your **bank statement** lines against **posted journal lines** on the linked GL cash/bank account. It supports PDF statement import (with optional AI parsing), automatic matching, manual matching, and adjustment journals.

---

## Where to find it

1. Sidebar **Accounting** → **Bank Accounts** — maintain bank master records linked to Chart of Accounts.
2. Sidebar **Accounting** → **Bank Reconciliation** — reconciliation sessions and import.

Permissions: `bank_accounts.view`, `bank_reconciliation.view`, `bank_reconciliation.import` (for statement upload).

---

## Bank Accounts setup

Before reconciling, each physical bank account must exist in **Bank Accounts** and be linked to a **postable COA** code (typically under `1.1.1.x` cash/bank).

- **Create** — Accounting → Bank Accounts → Add; select bank name, account number, and GL account.
- Sales Receipts and Purchase Payments post the cash leg to the **bank account COA** selected on the payment line (not a hardcoded default).

---

## Import bank statement

1. Go to **Bank Reconciliation** → **Import Statement**.
2. Select the **Bank Account** and statement period.
3. Upload a **PDF** bank statement.
4. The system extracts lines (PDF text + optional OpenRouter AI). Review parsed amounts and dates.
5. Save to create a **Bank Statement** with **statement lines**.

Statement **credit** (money in) corresponds to book **debit** on the bank GL account.

---

## Reconcile a session

1. Open a reconciliation session from the list (or start from an imported statement).
2. Review **unmatched statement lines** and **unmatched book lines** (from journals on that COA).
3. Use **Auto Match** (deterministic rules) or **AI Match** (optional) to propose pairs.
4. **Manual match** — link a statement line to a journal line.
5. Create **adjustment** entries for bank charges or timing differences (posts via standard journal posting).
6. **Complete** the reconciliation when the book balance ties to the statement closing balance.

---

## Tips

- Reconcile one bank account and period at a time.
- Ensure Sales Receipts / Purchase Payments are **posted** before expecting them on the book side.
- Requires `OPENROUTER_API_KEY` for AI import/matching features (server configuration).
