# Sales Receipt — HELP reference (Sarang ERP)

Use this file for in-app HELP (navbar **?**) questions about **Sales Receipts** (customer cash/bank receipts allocated to **posted Sales Invoices**). After edits, administrators run **`php artisan help:reindex`**.

---

## What is a Sales Receipt?

A **Sales Receipt** records money received from a **customer** and allocates it to one or more **posted** Sales Invoices (AR). It starts in **draft**, then someone with posting permission **posts** it to create accounting journals (cash/bank debit, accounts receivable credit). Document type is often abbreviated **SR**.

**Keywords:** sales receipt, SR, customer payment, AR receipt, collection, pelunasan faktur.

---

## Where to open Sales Receipts

1. Sign in to Sarang ERP.
2. In the sidebar under **Sales**, open **Sales Receipts** (list).
3. Open an existing receipt with **View**, or use **Create** for a new one.

**Keywords:** where is sales receipt menu, AR receipt list, `/sales-receipts`.

---

## Creating a sales receipt (draft)

You need permission **`ar.receipts.create`**.

1. **Sales Receipts** — **Create / new**.
2. Set **date**, **company entity**, **customer**, and optional **description**.
3. Select **posted** invoices for that customer; enter **allocation** amounts (how much of this receipt pays each invoice).
4. Add **receipt lines**: bank/cash accounts and amounts. The **total of receipt lines** must equal the **total allocations**.
5. Save. The receipt is stored as **draft**; a **receipt number** is assigned and does not need to be typed manually.

**Keywords:** new sales receipt, allocate to invoice, receipt lines must match allocation.

---

## Editing a draft sales receipt

You can **change** a sales receipt only while it is still **draft**. **Posted** receipts cannot be edited from the screen (open the receipt: there is no Edit button once posted).

**Who can edit:** same as create — permission **`ar.receipts.create`**.

**How:**

1. Open the **draft** receipt (**View** from the list).
2. Click **Edit** in the header (warning-style button next to **Post**).
3. Adjust **date**, **company**, **customer**, **description**, **invoice allocations**, and **receipt lines**. Rules are the same as on create: totals must match; invoices must belong to the selected customer and be **posted**; you cannot allocate more than the invoice **remaining** balance (the system ignores this receipt’s **current** allocations when calculating that limit so you can change amounts safely).
4. **Save** (**Update Receipt**). The **receipt number does not change**.
5. If you use **more than one receipt line**, line amounts are **not** auto-adjusted from allocations: keep the sum of line amounts equal to the sum of allocations.

**Keywords:** edit sales receipt, change draft receipt, correct allocation, wrong amount before post, update SR, modify sales receipt draft, fix customer payment draft.

---

## Posting a sales receipt

Posting turns the draft into accounting entries. You need permission **`ar.receipts.post`**.

1. Open the receipt.
2. Click **Post**.

**Keywords:** post sales receipt, finalize receipt, journal from SR.

---

## Permissions for sales receipts

| Permission            | Typical use                                      |
|-----------------------|--------------------------------------------------|
| **`ar.receipts.view`**| List and open receipts, PDF/print.               |
| **`ar.receipts.create`**| Create new receipts and **edit draft** receipts. |
| **`ar.receipts.post`**| Post a draft receipt (accounting impact).        |

**Keywords:** who can edit sales receipt, permission SR, ar.receipts.

---

## Related documents and invoices

- **Sales Invoice** must be **posted** before it can appear for allocation.
- Changing drafts may update which invoices are treated as **fully paid** and document **closure** links; posting still follows the same rules as before.

**Keywords:** invoice must be posted, allocation remaining balance.
