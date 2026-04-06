# Purchase Invoice (PI) — user guide (English)

## Introduction

Purchase Invoices record vendor billing and accounts payable. This short guide complements `purchase-invoice-manual-id.md` and highlights behaviour that matters for English-speaking admins.

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
