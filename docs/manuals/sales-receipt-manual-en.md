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
2. Set **date**, **company entity** (PT/CV), **customer**, and optional **description**.
3. Select **posted** invoices for that customer **for the same company entity** as the header; enter **allocation** amounts (how much of this receipt pays each invoice). Changing **Company** reloads the invoice list so CV invoices do not appear when PT is selected (and vice versa).
4. Add **receipt lines**: bank/cash accounts and amounts. The **total of receipt lines** may differ slightly from **total allocations** within the configured **rounding tolerance** (see **Payment rounding** below). When there is a difference, choose a **rounding account** (default **7.1.4 Selisih Pembulatan**).
5. Save. The receipt is stored as **draft**; a **receipt number** is assigned and does not need to be typed manually.

**Keywords:** new sales receipt, allocate to invoice, receipt lines, rounding tolerance.

---

## Create Receipt from a posted Sales Invoice

From a **posted** Sales Invoice that still has **remaining balance**, use the green **Create Receipt** button on the invoice **show** page header.

- Opens **Sales Receipts → Create** with `?sales_invoice_id={id}`.
- **Company**, **customer**, **description**, and **invoice allocation** are prefilled.
- The **Receipt Lines** section appears automatically with a **Bank/Cash Account** dropdown and amount matching the allocation (you still choose which cash/bank account to use).
- If the invoice is not posted, is opening balance-only in a blocked state, or is already fully allocated, the button is hidden or you are redirected with an error.

**Menu path:** **Sales** → **Sales Invoices** → open invoice → **Create Receipt**.

**Keywords:** create receipt from invoice, Create Receipt button, sales_invoice_id, prefilled SR, bank cash account missing, receipt lines not showing.

---

## Payment rounding (cash vs allocation)

Customers often pay **rounded cash** (e.g. invoice due **8,245,999.99**, customer pays **8,246,000.00**).

- **Allocation** to each invoice remains capped at the invoice **remaining balance** (exact amount due).
- **Receipt line total** (cash received) may differ from **total allocation** within tolerance.
- **`rounding_amount`** = total receipt lines − total allocations (stored on the document).
- Default tolerance: ERP parameter **`sales_receipt_rounding_tolerance`** (default **Rp 999,999**). Over tolerance → validation error.
- Default rounding GL account: **`rounding_account_id`** ERP parameter → **7.1.4 Selisih Pembulatan (Rounding)**; overridable per receipt on create/edit.
- On **post**, journal credits AR for the **allocated** amount and posts the rounding difference to the rounding account (gain or loss).

Configure tolerance/account: **Admin** → **ERP Parameters** (category accounting).

**Keywords:** payment rounding, pembulatan, selisih pembulatan, 7.1.4, rounding gain, rounding loss, pay rounded amount, tolerance sales receipt.

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

The journal **debits the cash/bank COA account on each receipt line** (not a fixed petty-cash account) and **credits accounts receivable** for the **allocated** total. If **`rounding_amount`** is non-zero, an additional line posts to the **rounding account** (credit for gain when customer paid more than allocated total, debit for loss when paid less). **Legacy data (before June 2026):** some posted receipts may still show Kas di Tangan in the GL instead of the selected bank — administrators repair with `php artisan sales-receipts:repair-bank-journals --dry-run` (see `docs/decisions.md`).

**Keywords:** post sales receipt, finalize receipt, journal from SR, bank account receipt line.

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
- Only invoices with the **same `company_entity_id`** as the receipt **Company** field are offered (`getAvailableInvoices` requires `company_entity_id`). Saving rejects allocations to invoices from another entity.
- Changing drafts may update which invoices are treated as **fully paid** and document **closure** links; posting still follows the same rules as before.

## Relationship Map (Sales Receipt in the chain)

On a **Sales Receipt** show page, click **Relationship Map** in the header to open the **Document Workflow** diagram.

- Links **Sales Receipt** to allocated **Sales Invoice(s)** and expands upstream when data exists: **Delivery Order** → **Sales Order** (and **Sales Quotation** when converted from SQ).
- **Base / Target** buttons on the show page use the same underlying document links.
- If the map was empty for **older receipts** created before relationship sync was enabled, open and **save** a draft edit (or ask an administrator to run a relationship sync) so **Sales Invoice → Sales Receipt** links are stored. New receipts sync links automatically on **create** and **update**.

**Keywords:** relationship map sales receipt, SR workflow diagram, SO DO SI SR chain, document map empty receipt, invoice must be posted, allocation remaining balance, company entity filter sales receipt, PT CV receipt wrong invoices, filter invoice by entity.
