# Sales workflow corrections — HELP summary (English)

This file supports the in-app **HELP** assistant (navbar **?**). Chunks are split at `##` headings. After edits, run **`php artisan help:reindex`** on the server.

## Sales Credit Memo — one memo per Sales Invoice

- **Menu**: **Sales** → **Sales Credit Memos** (`/sales-credit-memos`). Create via **Create** or from **Sales Invoice** detail (**Create Credit Memo** when shown).
- **Rule**: only **one** Sales Credit Memo per **Sales Invoice** (unique `sales_invoice_id`). If a memo already exists, the app redirects or shows an error on duplicate create.
- **Post** the memo (permission `ar.credit-memos.post`) to record reversing journal entries.
- **User keywords**: credit memo, correct posted invoice, AR adjustment, one CM per SI.

## Reverse delivery — after delivered or completed

- **Menu**: **Sales** → **Delivery Orders** → open a DO (`/delivery-orders/{id}`).
- **Button**: **Reverse delivery** (not **Cancel delivery order**). Cancel applies to early statuses only; **Reverse** applies to **partial_delivered**, **delivered**, or **completed**.
- **Permission**: `delivery-orders.reverse`.
- **Effect**: reverses journals tied to the DO, restores stock from recorded sale transactions, sets DO status to **reversed** (see app behaviour for closure fields).
- **Keywords**: reverse DO, undo shipment, wrong delivery, stock return after delivery.

## Reverse delivery when a Sales Invoice existed

- The DO must **not** still be linked to a sales invoice in the pivot (internal **unlink** procedure).
- If the DO was **closed** by a sales invoice, a **posted Sales Credit Memo** for that invoice is typically required before reversal is allowed (see on-screen messages).
- **Keywords**: unlink DO from invoice, invoiced delivery, CM before reverse.

## Relationship Map and Document Workflow diagram

- **Button** on document detail pages: **Relationship Map** in the header.
- **Modal**: **Document Relationship Map**; diagram title **Document Workflow**.
- **Node labels** include **document type** (e.g. Sales Order, Delivery Order), **number**, **date**, **Status** line, and **amount**. Reference text appears only when a reference exists (see UI behaviour).
- **Keywords**: document map, workflow diagram, SO DO SI chain, N/A in diagram.

## Wrong Company entity on Sales Order

- **Field**: **Company entity** on the SO form.
- If the **Sales Invoice is not posted**, often fix by **Edit** SO and change entity (subject to document rules).
- If the **Sales Invoice is posted**, usually need a **Sales Credit Memo**, then fix DO links and **Reverse delivery** if used, then recreate under the correct entity.
- **Long checklist**: `checklist-perbaikan-salah-entitas-so-id.md` (Indonesian) in `docs/manuals/`.
- **Keywords**: wrong entity, PT vs CV, company entity, fix SO entity.

## Permissions (quick reference)

| Action | Example permissions |
|--------|---------------------|
| Credit memos | `ar.credit-memos.view`, `ar.credit-memos.create`, `ar.credit-memos.post` |
| Reverse DO | `delivery-orders.reverse` |
| Post SI | `ar.invoices.post` |
