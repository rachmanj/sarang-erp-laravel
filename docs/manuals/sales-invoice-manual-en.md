# Sales Invoice (AR) User Manual

## Overview

### What is a Sales Invoice?

A **Sales Invoice (SI)** is the customer billing document that records **accounts receivable (AR)** and integrates with **Sales Orders**, **Delivery Orders**, **Sales Quotations**, and accounting journals (receivables, revenue, output VAT, etc.).

### Common entry points

| Source | Description |
|--------|-------------|
| **From Delivery Order** | Main flow: goods shipped first (DO **delivered** / **completed**), then invoice. |
| **From Sales Quotation** | Create screen opened with `quotation_id` — lines prefilled from the quotation. |
| **Manual** | **Sales** → **Sales Invoices** → **Create** without a DO — enter customer and lines (e.g. services). |
| **Direct Sales** | **Sales** → **New Direct Sale**, or SI **Create** with **Direct Sale** enabled — sell and issue stock on **Post** (credit or cash). See `direct-sales-manual-en.md`. |

---

## Menu, permissions, and navigation

### Where to find it

1. Sign in to Sarang ERP.
2. Sidebar **Sales** → **Sales Invoices**.

### Permissions (typical)

| Action | Permission |
|--------|------------|
| View list & detail | `ar.invoices.view` |
| Create & edit | `ar.invoices.create` |
| Post / unpost | `ar.invoices.post` |

### URLs

- List: `/sales-invoices`
- Create: `/sales-invoices/create`

---

## Typical business flow

1. **Sales Order** approved.
2. **Delivery Order** created → approved → **Mark as Delivered** → DO status **delivered** or **completed**.
3. **Sales Invoice** from DO (**Create Invoice from Delivery Order**) or from the SI create screen with eligible DOs.
4. **Post** the SI to recognise AR and revenue in the books.

More detail on shipping: see **Delivery Order** manual (`delivery-order-manual.md` / Indonesian `delivery-order-manual-id.md`).

---

## Creating a Sales Invoice from a Delivery Order

### Delivery Order prerequisites

- DO status is **delivered** or **completed**.
- The DO is **not yet invoiced** (system prevents duplicate invoicing for the same DO).
- If multiple DOs are selected, **same customer** and **same company entity**.

### Steps from the Delivery Order page

1. Go to **Sales** → **Delivery Orders** and open a completed DO.
2. Click **Create Invoice from Delivery Order**.
3. Review prefilled lines from delivery quantities, taxes, and terms.
4. Save as **Draft**, then **Post** when ready (requires post permission).

---

## Creating from a Sales Quotation

Open create with **`quotation_id`** (from the quotation workflow). Header and lines are prefilled from the **Sales Quotation**. Adjust date and mandatory fields, then save and post.

---

## Manual creation (without a DO)

**Sales** → **Sales Invoices** → **Create**. Select customer, company entity, date, and add invoice lines (revenue account, qty, price, tax codes). Use **Disc %** / **Disc Amt** per line and header **Discount (%)** / **Discount Amount** when needed (see [Line and header discounts](#line-and-header-discounts)). Save draft → **Post**.

---

## Line and header discounts

### Line discount

- **What it does**: Reduces that line’s **DPP** (qty × unit price) **before** PPN and WTax are calculated—same idea as **Sales Order** and **Sales Quotation** lines.
- **How to enter**: **Disc %** or **Disc Amt** on each line; changing one updates the other based on gross DPP for that line.
- **From Delivery Order**: When the SI is prefilled from a DO, line discounts can be copied from the **Sales Order** (percentage kept; flat amount scaled by delivered quantity vs SO line qty when applicable).

### Header discount

- **What it does**: A **document-level** reduction applied **after** all line totals are summed. It is **not** stored per line.
- **How to enter**: Header **Discount (%)** or **Discount Amount** on create/edit; changing one updates the other based on the **sum of line amounts** (incl. tax).

### Totals and stored amounts

- Invoice **`total_amount`** is the **amount due** after the header discount (used for print footers, allocations, and customer API **`total_amount`**).
- **Show / print**: Footer may show **Gross total (incl. tax)** and **Header discount** before **Amount due** when a header discount exists.

### Keywords (HELP)

sales invoice discount, header discount, line discount, diskon faktur, DPP discount.

---

## List, detail, print, import

- Use the **Sales Invoices** index to filter by **date range** (from/to), **search** (invoice no, customer, reference), **status** (draft/posted), and **company entity** (PT/CV radio).
- The list footer shows **Totals (filtered)** for the same filters as the grid.
- **Export Excel**: click **Export Excel** in the header; the file uses the **same filters** as the table (not only the current page). Columns: Date, SI No, Customer, Customer Ref No, Total, Status, plus a totals row.
- **Print / PDF**: from the SI detail page — **Standard** vs **Dot matrix** layouts may be available (`?layout=dotmatrix` where supported).
- **Import**: if enabled, use the import wizard and template under the SI import routes.

**Keywords (HELP):** export sales invoice excel, filter sales invoice list, SI export, download invoice list.

---

## Line amounts, VAT, and totals (what you see vs what is stored)

- **Stored line `amount`**: Tax-inclusive **line total** (net DPP after line discount, then PPN on that base, minus WTax on that base)—aligned with **`SalesOrderLine::computeAmountFromPricing`**.
- **Discount column (show and print)**: Line **DPP** discount amount when greater than zero.
- **Amount column (show and print)**: Stored line **`amount`** (tax-inclusive gross for the line), not raw qty × unit price.
- **Footer**: **Subtotal (ex. PPN)** is sum of **net DPP** (after line discounts). Then **PPN / VAT**, **WTax** if any, then **Gross total** / **Header discount** when a header discount exists, then **Amount due**—which matches header **`total_amount`** and is the figure used for payment allocation.

---

## Posting and accounting

**Post** locks the document and creates journals via **`PostingService`**, using **`SalesInvoicePostingMath`** for DPP (after line discounts), PPN, WTax, and **amount due** (including header discount when present). **Opening balance** invoices use a different pattern (AR gross, retained opening, PPN as applicable).

**Unpost** may be available for corrections depending on policy and permissions.

### Auditing posted invoices (CLI)

Administrators and support can run:

`php artisan sales-invoices:validate-posted-journals`

Optional filters: `--id=` (single invoice), `--limit=` (batch). The command compares posted journal lines to the expected split from invoice lines and **`total_amount`** (amount due).

---

## Sales Credit Memo

- **Menu**: **Sales** → **Sales Credit Memos** (`/sales-credit-memos`). Or from a **posted** SI: **Create Credit Memo** / **Credit Memo** when applicable.
- **Rule**: **One** Sales Credit Memo per **Sales Invoice** (duplicates are blocked).
- **Post** the memo (`ar.credit-memos.post`) to record reversing entries.
- For workflow corrections (entity, reverse DO), see **`sales-workflow-corrections-help-en.md`** and the Indonesian checklist **`checklist-perbaikan-salah-entitas-so-id.md`**.

---

## Document numbering

Follows the **document numbering** rules per company entity. See **Document Numbering System** manuals (`document-numbering-system-manual-en.md` / `document-numbering-system-manual-id.md`).

---

## Troubleshooting

### Cannot create SI from DO

- Confirm DO is **delivered** or **completed** and not already invoiced.
- For multi-DO, same customer and entity.

### "Sales Invoice already exists for this Delivery Order"

- The DO is already linked to an SI — open that invoice or follow your reversal process.

### HELP assistant

Use the **?** icon in the navbar for how-to questions. Knowledge comes from `docs/manuals/`; after manual updates, run **`php artisan help:reindex`** on the server.

---

## Related manuals

- `delivery-order-manual-id.md` — shipping and “create invoice from DO”.
- `customer-project-manual-id.md` — projects on invoices.
- `in-app-help-manual-en.md` — in-app HELP and updating knowledge.
- `sales-workflow-corrections-help-en.md` — credit memos, reverse delivery, Relationship Map, wrong entity (HELP-oriented).
