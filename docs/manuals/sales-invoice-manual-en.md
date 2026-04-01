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

**Sales** → **Sales Invoices** → **Create**. Select customer, company entity, date, and add invoice lines (revenue account, qty, price, tax codes). Save draft → **Post**.

---

## List, detail, print, import

- Use the **Sales Invoices** index to filter by date, customer, entity, status.
- **Print / PDF**: from the SI detail page — **Standard** vs **Dot matrix** layouts may be available (`?layout=dotmatrix` where supported).
- **Import**: if enabled, use the import wizard and template under the SI import routes.

---

## Posting and accounting

**Post** locks the document and creates journals (AR, revenue, VAT, etc. per account mapping). **Unpost** may be available for corrections depending on policy and permissions.

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
