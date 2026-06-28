# Direct Sales (Sales Invoice mode)

## Overview

### What is Direct Sales?

**Direct Sales** is a mode on **Sales Invoice** that skips **Sales Order** and **Delivery Order**. You sell and issue stock in one step when you **Post** the invoice.

Use it for counter sales, walk-in customers, or any sale where you do not need the full SO → DO → SI chain.

The document is still a normal **Sales Invoice** (numbering code **08**, same permissions as SI).

### When to use Direct Sales vs normal Sales Invoice

| Situation | Recommended path |
|-----------|------------------|
| Goods already shipped via DO | **Sales Invoice from Delivery Order** (normal SI) |
| Counter / cash sale, stock leaves now | **Direct Sales** |
| Credit sale without prior DO, stock leaves on invoice post | **Direct Sales** (payment = Credit) |
| Service-only billing (no stock) | Normal **Sales Invoice** manual create (no Direct Sale flag) |

---

## Menu, permissions, and navigation

### Where to find it

1. Sidebar **Sales** → **New Direct Sale**, or
2. **Sales** → **Sales Invoices** → **Create** → enable **Direct Sale**, or
3. Navbar **Menu Search** (Ctrl+K) → type **direct sales** → **Direct Sales**.

### URL

- Create: `/sales-invoices/create?direct=1`

### Permissions

| Action | Permission |
|--------|------------|
| Create Direct Sale | `ar.invoices.create` |
| Post (issue stock + journals) | `ar.invoices.post` |
| View list & detail | `ar.invoices.view` |

---

## Creating a Direct Sale

### Header

1. Select **Customer** and **Company entity**.
2. Check **Direct Sale** (on by default when opened from **New Direct Sale**).
3. Choose **Payment**:
   - **Credit (AR — pay later)** — invoice stays on AR until a **Sales Receipt** is recorded.
   - **Cash (paid now)** — select **Cash / Bank account** (e.g. Kas di Tangan `1.1.1.01`). System auto-creates and posts a **Sales Receipt** when you post the invoice.

### Lines

Each line **must** have an **inventory item** (use the search button on the line).

1. Search and select the item (code, name, selling price fill in).
2. Enter **Qty** and **Unit price** (DPP, tax-exclusive).
3. Set **VAT** / **WTax** and discounts if needed.

You cannot link a Direct Sale to a **Sales Order** or **Delivery Order**.

### Save and post

1. **Save Invoice** — status **Draft**.
2. **Post** — stock is reduced, revenue/COGS/VAT journals are created.
3. If **Cash** payment, a **Sales Receipt** is posted automatically and the invoice is fully paid.

On the invoice detail page you will see badges **Direct Sale** and **Cash (Paid)** or **Credit**.

---

## Accounting at post (summary)

Direct Sale posting (one journal on the invoice):

- Credit **Revenue** (per line, net DPP after discounts)
- Credit **PPN Keluaran** when VAT applies
- Debit **COGS**, Credit **Inventory Available** for stock items
- Debit **Accounts Receivable** for amount due
- Debit **PPh 23 prepaid** when customer WTax applies

**Cash** payment adds a second journal: Debit Cash/Bank, Credit AR (auto Sales Receipt).

Normal SI from DO still uses **AR UnInvoice** conversion; Direct Sale does **not**.

---

## After posting

### Credit Direct Sale

- Collect payment later: **Sales** → **Sales Receipts** → **Create** → allocate to this invoice (same as any posted SI).

### Cash Direct Sale

- Invoice is already allocated; **Create Receipt** is hidden on the invoice page.

### Relationship Map

- Cash Direct Sales may show a link to the auto **Sales Receipt**.

---

## Troubleshooting

### Post fails: insufficient stock

- Available quantity (FIFO) is less than line qty. Reduce qty, choose another item, or receive stock first (GRPO / purchase / adjustment).

### Post fails: missing inventory item on line

- Every Direct Sale line needs an item selected via the item search — not a free-text revenue line only.

### Wrong payment method

- Only **draft** invoices can be edited. Delete draft and recreate, or use credit memo / receipt workflows for posted documents per company policy.

---

## Related manuals

- **Sales Invoice** — `sales-invoice-manual-en.md` (list, discounts, print, import)
- **Sales Receipt** — `sales-receipt-manual-en.md` (collecting AR after credit direct sale)
- **Delivery Order** — when you need shipping before invoicing

After editing this file, run **`php artisan help:reindex`**.
