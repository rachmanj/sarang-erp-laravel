# Account Statements module (formal GL / Business Partner statements)

## What this feature is

**Accounting → Account Statements** creates **numbered** statements stored in the database (`AST-…` style numbers). Each record has **lines**, **opening/closing balances**, and a **status** workflow.

This is **not** the same as the **Business Partner** screen tab **Account statement**, which reads **posted journals** live from the GL for a partner. That tab is documented in **`business-partner-module-manual.md`**. Use this module when you need a **saved, printable** statement document for an **account** or **partner** over a period.

## Generating a statement

1. Go to **Accounting → Account Statements →** use **Generate** (or open **`/account-statements/create`**).
2. Choose **Statement type**:
   - **GL Account Statement** — pick an **Account** (Chart of Accounts).
   - **Business Partner Statement** — pick a **Business Partner**.
3. Set **From date** and **To date** (required). Optionally narrow by **Project** or **Department** when available.
4. Click **Generate Statement**.

If the page returns with a red **Please fix the following** box, read the messages (for example missing account, invalid dates, or permission issues).

## Deep link for GL account

You can pre-select type and account from the URL, for example:

`/account-statements/create?statement_type=gl_account&account_id=<id>`

Replace `<id>` with the account’s primary key from **Accounts** (IDs differ per database).

## Statement status (Draft, Finalized, Cancelled)

| Status | Meaning |
|--------|---------|
| **Draft** | Just generated; you can **edit** notes (where allowed), **finalize**, or **delete**. |
| **Finalized** | Locked; **cannot** delete or cancel from the UI rules in place. |
| **Cancelled** | Marked void (set via backend route; there is **no** Cancel button on the standard screens today). |

New statements are created as **Draft**.

## How to change status

- **Draft → Finalized**: Open the statement (**View** / eye icon). On the detail page, click **Finalize** (confirm). You need permission **`account_statements.update`**. The statement must have **at least one line** (transactions in range); otherwise finalization is rejected.
- **Draft → removed**: Use **Delete** on the list or detail page (permission **`account_statements.delete`**). This **removes** the record; it does not set status to Cancelled.
- **Finalized**: Cannot be deleted or cancelled through the current UI actions.

You can also tap the **green check** on the index row for a draft to finalize quickly.

## Permissions

- **`account_statements.view`** — list and open statements.
- **`account_statements.create`** — generate new statements.
- **`account_statements.update`** — edit draft notes, **finalize**.
- **`account_statements.delete`** — **delete** non-finalized statements.

## Troubleshooting

### “Nothing happens” when I click Generate Statement

- Look for a **red validation alert** at the top of the form.
- Ensure **Statement type** matches the field you filled (**GL** needs **Account**; **Business Partner** needs **Business Partner**).
- Ensure **To date** is **on or after** **From date**.

### Finalize button missing or disabled

- Only **Draft** statements show **Finalize**. If already **Finalized**, the action is hidden.
- If finalize fails with a message about **transactions**, the period may have **no lines**; check journals and dates.

## Related documentation

Technical reference: **`docs/ACCOUNT-STATEMENTS-IMPLEMENTATION.md`**. Partner GL tab (different feature): **`docs/BUSINESS-PARTNER-ACCOUNT-STATEMENT.md`**.
