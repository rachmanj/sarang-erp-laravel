# Sales Invoice Opening Balance Implementation

**Date**: 2026-02-04  
**Status**: ✅ COMPLETE  
**Purpose**: Allow recording Sales Invoices as opening balances with explicit flag for consistency with Purchase Invoice module

---

## Problem Statement

When migrating to a new ERP system or recording historical transactions as of January 1, 2026, you need to:

-   Record Sales Invoices for existing AR balances
-   Create proper accounting entries (AR receivable)
-   Have explicit control over opening balance invoices (not just inferred from missing Sales Order)

**Previous Behavior**: Sales Invoice used `!$invoice->sales_order_id` to infer opening balance status, which was implicit and not always accurate.

---

## Solution: `is_opening_balance` Flag

Added a boolean flag `is_opening_balance` to Sales Invoices that:

-   ✅ Provides explicit control over opening balance invoices
-   ✅ Creates accounting entries (AR receivable + Revenue)
-   ✅ Posts directly to AR and Revenue accounts (skips AR UnInvoice flow)
-   ✅ Consistent with Purchase Invoice module
-   ✅ Can be allocated to Sales Receipts normally
-   ✅ Visible in reports and statements

---

## Implementation Details

### 1. Database Changes

**Migration**: `2026_02_04_163411_add_is_opening_balance_to_sales_invoices_table.php`

```php
Schema::table('sales_invoices', function (Blueprint $table) {
    $table->boolean('is_opening_balance')->default(false)->after('sales_order_id');
});
```

**Field**: `is_opening_balance` (boolean, default: false)

---

### 2. Model Updates

**File**: `app/Models/Accounting/SalesInvoice.php`

-   Added `is_opening_balance` to `$fillable` array
-   Added `is_opening_balance` to `$casts` as boolean

---

### 3. Controller Logic

**File**: `app/Http/Controllers/Accounting/SalesInvoiceController.php`

#### Posting Logic (Line 235)

```php
// Check if this is an opening balance invoice
// Opening balance invoices post directly to AR and Revenue accounts
$isOpeningBalance = $invoice->is_opening_balance;
```

**Key Change**: Replaced implicit check `!$invoice->sales_order_id` with explicit `$invoice->is_opening_balance` flag.

#### Store Method (Line 109)

-   Added `is_opening_balance` to validation rules
-   Added `is_opening_balance` to invoice creation
-   Reads from `$request->boolean('is_opening_balance', false)`

---

### 4. UI Changes

#### Create Form (`resources/views/sales_invoices/create.blade.php`)

-   Added checkbox: "Opening Balance Invoice"
-   Added helpful tooltip explaining accounting flow
-   Positioned after Description/Due Date fields

#### Show Page (`resources/views/sales_invoices/show.blade.php`)

-   Displays warning badge: "Opening Balance Invoice"
-   Shows message: "This invoice posts directly to AR and Revenue accounts"

---

## Accounting Flow Comparison

### Opening Balance Invoice (Posted)

**When `is_opening_balance = true`**:

**Journal Entry Created**:

```
Debit:  Piutang Dagang (1.1.2.01)                    [AR Account]
Credit: Saldo Awal Laba Ditahan (3.3.1)              [Retained Earnings Opening Balance]
Credit: PPN Keluaran (2.1.2)                         [VAT Output, if applicable]
```

**Result**:

-   ✅ AR receivable recorded correctly
-   ✅ Retained Earnings Opening Balance credited (not Revenue accounts)
-   ✅ Can allocate receipts
-   ✅ Appears in AR aging reports
-   ✅ **No AR UnInvoice intermediate account used**
-   ✅ **Correct accounting treatment for historical opening balances**

---

### Regular Invoice (Posted)

**When `is_opening_balance = false`** (has Sales Order):

**Journal Entry Created**:

```
Debit:  AR UnInvoice (1.1.2.04)          [Reducing un-invoiced receivable]
Credit: Piutang Dagang (1.1.2.01)        [Creating AR receivable]
Credit: PPN Keluaran (2.1.2)             [VAT Output, if applicable]
```

**Note**: Revenue recognition is handled by Delivery Order, not Sales Invoice.

---

## Usage Guide

### Recording Opening Balance Sales Invoices

1. **Navigate to**: `Sales > Sales Invoices > Create`

2. **Fill in invoice details**:

    - Date: **January 1, 2026** (or your opening balance date)
    - Customer: Select the customer
    - **Check**: ✅ "Opening Balance Invoice" checkbox

3. **Add line items**:

    - Select Revenue Accounts (from Chart of Accounts)
    - Add descriptions, quantities, prices
    - Include VAT codes if applicable

4. **Save and Post**:

    - Invoice creates accounting entries:
        - Debit: AR Account (Piutang Dagang)
        - Credit: Revenue Accounts (from lines)
        - Credit: VAT Output (if applicable)
    - **Posts directly to AR and Revenue** (no AR UnInvoice flow)

5. **Allocate Receipts**:
    - Can allocate Sales Receipts normally
    - AR balance reduces as receipts are received

---

## Behavior Comparison

| Scenario                 | AR UnInvoice Flow         | Direct AR Posting   | Revenue Recognition |
| ------------------------ | ------------------------- | ------------------- | ------------------- |
| **Regular SI (with SO)** | ✅ Uses AR UnInvoice      | ✅ Creates AR       | ✅ Done at DO       |
| **Opening Balance SI**   | ❌ **Skips AR UnInvoice** | ✅ **Direct to AR** | ✅ **Direct at SI** |

---

## Key Differences from Purchase Invoice

| Aspect                      | Purchase Invoice                             | Sales Invoice                                 |
| --------------------------- | -------------------------------------------- | --------------------------------------------- |
| **Inventory Impact**        | ✅ Can affect inventory (if direct purchase) | ❌ No direct inventory impact (handled at DO) |
| **Opening Balance Purpose** | Skip inventory transactions                  | Skip AR UnInvoice flow, post directly         |
| **Accounting Flow**         | AP UnInvoice → Utang Dagang                  | Direct: AR + Revenue                          |

---

## Benefits

1. ✅ **Explicit Control**: Clear flag instead of inferring from missing Sales Order
2. ✅ **Consistency**: Same pattern as Purchase Invoice module
3. ✅ **Proper Accounting**: Direct AR and Revenue posting for opening balances
4. ✅ **Audit Trail**: Flag provides clear audit trail
5. ✅ **Flexible**: Can be used for any historical invoice recording

---

## Testing Checklist

-   [x] Migration runs successfully
-   [x] Checkbox appears in create form
-   [x] Opening balance invoice posts with direct AR/Revenue flow
-   [x] Regular invoice still uses AR UnInvoice flow
-   [x] Badge displays on show page
-   [x] Can allocate receipts to opening balance invoices
-   [x] Accounting entries created correctly

---

## Example Use Case

**Scenario**: Recording opening AR balance of Rp 75,000,000 from Customer XYZ as of January 1, 2026

**Steps**:

1. Create Sales Invoice

    - Date: 2026-01-01
    - Customer: Customer XYZ
    - ✅ Check "Opening Balance Invoice"
    - Add line: Revenue Account "4.1.1 - Penjualan", Amount Rp 75,000,000

2. Post Invoice

    - ✅ Journal entry created:
        - Debit: Piutang Dagang (AR) Rp 75,000,000
        - Credit: Penjualan (Revenue) Rp 75,000,000
    - ✅ AR receivable recorded: Rp 75,000,000

3. Allocate Receipt (when payment received)
    - Create Sales Receipt
    - Allocate to this invoice
    - AR balance reduces

---

## Related Files

-   Migration: `database/migrations/2026_02_04_163411_add_is_opening_balance_to_sales_invoices_table.php`
-   Model: `app/Models/Accounting/SalesInvoice.php`
-   Controller: `app/Http/Controllers/Accounting/SalesInvoiceController.php`
-   Views:
    -   `resources/views/sales_invoices/create.blade.php`
    -   `resources/views/sales_invoices/show.blade.php`

---

## Summary

The `is_opening_balance` flag provides explicit control over Sales Invoices recorded as opening balances. Unlike Purchase Invoice (which skips inventory transactions), Sales Invoice opening balance flag:

-   **Skips AR UnInvoice flow**
-   **Posts directly to AR and Revenue accounts**
-   **Provides consistent pattern with Purchase Invoice module**

**Status**: ✅ Implementation Complete and Ready for Use
