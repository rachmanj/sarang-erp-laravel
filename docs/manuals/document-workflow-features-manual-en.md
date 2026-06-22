# Document workflow features — HELP reference (English)

This manual covers **document navigation**, **journal preview**, **Open/Closed list filters**, **Create Target Document** buttons, and **document deletion** shipped in Sarang ERP (2026-06).

---

## Base / Target document navigation

On document **show** pages (Purchase Order, GRPO, PI, PP, SO, DO, SI, SR, Sales Quotation, etc.), the **Base / Target Document** card lets you jump to linked upstream and downstream documents without opening the Relationship Map.

- **Base documents** — sources this document was created from (e.g. PI shows PO and/or GRPO).
- **Target documents** — documents created from this one (e.g. PO shows GRPO and PI).
- Purchase Order shows the card but **no Preview Journal** button (PO has no GL posting).

Menu: open the document from **Purchase** or **Sales** sidebar lists, then scroll to the navigation card on the show page.

---

## Preview Journal (before posting)

For posted-capable documents (GRPO, PI, PP, DO, SI, SR), click **Preview Journal** in the Base/Target card to see the **exact journal lines** that will be created on post — same logic as posting (`JournalBuilders`).

Supported document types: Goods Receipt PO, Purchase Invoice, Purchase Payment, Delivery Order, Sales Invoice, Sales Receipt.

If preview is empty, check that lines, accounts, and tax codes are complete on the draft document.

---

## Open / Closed filter on document lists

Index pages for **Purchase Orders**, **GRPO**, **PI**, **PP**, **Sales Orders**, **DO**, **SI**, and **SR** include an **All / Open / Closed** switch (default **Open**).

- **Open** — operationally outstanding documents (e.g. unpaid invoice, uninvoiced GRPO, partially received PO).
- **Closed** — fully settled or completed chain state.
- **All** — no filter.

The filter is computed from live allocations and quantities, not only the stored `closure_status` field.

---

## Create Target Document buttons

From a document show page you can create the next step in the chain:

| From | Button | Creates |
|------|--------|---------|
| Purchase Order | Copy to GRPO / Copy to Purchase Invoice | GRPO or PI with lines copied |
| GRPO | Create Purchase Invoice | PI prefilled from GRPO |
| Posted PI | Create Payment | Purchase Payment with vendor + invoice allocation |
| Sales Quotation | Convert to Sales Order | SO from quotation |
| Sales Order | Create Delivery Order | DO (when allowed) |
| DO (delivered) | Create Invoice from Delivery Order | Sales Invoice |
| Posted SI | Create Receipt | Sales Receipt with customer + invoice allocation |

Posted PI→PP and SI→SR open the payment/receipt create screen with **partner, entity, and invoice checkbox** already selected.

---

## Document deletion

On supported document show pages, a red **Delete** split button offers:

1. **Delete this document only** — removes only the current document (and reverses its journal if posted). **Blocked** if downstream target documents still exist.
2. **Delete with related documents** — cascade delete in leaf-first order; posted documents get **reversing journals** before removal.

Permissions (examples): `ap.invoices.delete`, `ar.invoices.delete`, `goods-receipt-pos.delete`, `delivery-orders.delete`, etc.

**Closed accounting periods** block deletion. The confirmation modal shows a **delete preview** list before you confirm.

Documents with downstream targets cannot use single delete until targets are removed first.
