# Purchase Invoice (PI) — user guide (English)

## Introduction

Purchase Invoices record vendor billing and accounts payable. This short guide complements `purchase-invoice-manual-id.md` and highlights behaviour that matters for English-speaking admins.

## Purchase pricing when PO + GRPO are involved

- **Quantities received** are recorded on the **Goods Receipt PO (GRPO)**.
- **Negotiated purchasing prices** are driven by the **Purchase Order (PO)**:
  - When you save a GRPO linked to a **Purchase Order**, the system sets **`unit_price`**, extended **`amount`**, **`account_id`**, and **`tax_code_id`** on each receipt line from the matching **PO line** (first PO line per item on that PO).
  - When you open a Purchase Invoice via **Create Invoice** on a GRPO, **pull lines**, or select several GRPOs on the PI create page, prefilled PI **unit prices** also come from **that PO** (not merely from inventory card prices). Quantities remain from **GRPO** lines.

## Multi-GRPO selection on Purchase Invoice create

- On **Create Purchase Invoice**, the **Invoice from supplier GRPO** panel lists **open GRPOs** for the supplier you chose; documents from **any company entity** may appear—the option label shows the entity code/name so you can pick PT vs CV (etc.).
- You do **not** need to align **Company** on the PI header simply to populate the GRPO drop-down—choose **Vendor**, **Refresh list**, then select lines.
- Use **Pull lines from selected GRPOs** to load rows (qty from GRPO, unit price rules from PO as above); the PI **Company** field is synced from the prefill payload where applicable.

## Invoice line totals (footer rows)

Footer rows under **Invoice Lines** follow this pattern:

| Row | Meaning |
|-----|---------|
| **Net subtotal (excl. VAT / WTax)** | Sum of `(Qty × Unit Price)` minus line discounts, before VAT and WTax |
| **VAT** | Total VAT (PPN) from line taxes |
| **WTax** | Total withholding amounts from line selections |
| **Total discount** | Line discounts plus header discount inputs |
| **Amount due** | Payable grand total |

## Artisan backfill — fix historical GRPO line prices

If GRPO rows with **`purchase_order_id`** captured wrong prices earlier, an operator can reconcile line amounts against the PO (see Indonesian manual § *Pemeliharaan*).

```bash
php artisan grpo:repair-lines-from-po-pricing --dry-run
php artisan grpo:repair-lines-from-po-pricing --grpo=123
php artisan grpo:repair-lines-from-po-pricing
```

This **does not reverse or repost journals or inventory**—accounting reconciliation may still be required after you change stored amounts.

## Invoice date validation

### Rule

When **creating** a PI or **updating a draft** (not yet posted), **Date** must be **on or before today** in the **application timezone** (`APP_TIMEZONE`).

### Exceptions

1. **Opening balance** — Check **Opening Balance Invoice** on the form. Future document dates are allowed when needed for opening-balance cutover (aligned with source documents).
2. **Permission** — Users with **`ap.invoices.future_date`** may enter a date **after today** without opening balance. Grant this in **Admin → Roles** (superadmin typically has all permissions).

### If validation fails

The error states that the date cannot be after today unless one of the exceptions applies. Fix the date, use opening balance when appropriate, or ask an administrator to assign **`ap.invoices.future_date`**.

### Technical note

Validation runs in `PurchaseInvoiceController` on `store` and `update`. Posting a draft does not change the date via a separate request.

## Related manuals

- Indonesian (full): `docs/manuals/purchase-invoice-manual-id.md`
- Module overview: `docs/manuals/purchase-module-manual.md`

After edits to manuals, run `php artisan help:reindex` on each environment that uses in-app HELP.
