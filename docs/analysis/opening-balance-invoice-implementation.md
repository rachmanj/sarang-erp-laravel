# Opening Balance Invoice Implementation

**Date**: 2026-02-04  
**Status**: ✅ COMPLETE  
**Purpose**: Allow recording Purchase Invoices as opening balances without affecting inventory quantities

---

## Problem Statement

When migrating to a new ERP system or recording historical transactions as of January 1, 2026, you need to:

-   Record Purchase Invoices for existing AP balances
-   Create proper accounting entries (AP liability)
-   **NOT** affect inventory quantities (stock already exists)

**Current Behavior**: Direct Cash Purchase Invoices automatically create inventory transactions when posted, which would incorrectly increase stock for opening balance invoices.

---

## Solution: `is_opening_balance` Flag

Added a boolean flag `is_opening_balance` to Purchase Invoices that:

-   ✅ Allows recording invoices as opening balances
-   ✅ Creates accounting entries (AP liability)
-   ✅ **Skips inventory transaction creation**
-   ✅ Can be allocated to Purchase Payments normally
-   ✅ Visible in reports and statements

---

## Implementation Details

### 1. Database Changes

**Migration**: `2026_02_04_151212_add_is_opening_balance_to_purchase_invoices_table.php`

```php
Schema::table('purchase_invoices', function (Blueprint $table) {
    $table->boolean('is_opening_balance')->default(false)->after('is_direct_purchase');
});
```

**Field**: `is_opening_balance` (boolean, default: false)

---

### 2. Model Updates

**File**: `app/Models/Accounting/PurchaseInvoice.php`

-   Added `is_opening_balance` to `$fillable` array
-   Added `is_opening_balance` to `$casts` as boolean

---

### 3. Controller Logic

**File**: `app/Http/Controllers/Accounting/PurchaseInvoiceController.php`

#### Posting Logic (Line 497)

```php
// Create inventory transactions for direct purchases (but NOT for opening balance invoices)
if ($invoice->is_direct_purchase && !$invoice->is_opening_balance) {
    // Create inventory transactions...
}
```

**Key Change**: Added `&& !$invoice->is_opening_balance` condition to skip inventory transactions for opening balance invoices.

#### Unposting Logic (Line 585)

```php
// Delete inventory transactions if any (for direct purchases, but not opening balance)
if ($invoice->is_direct_purchase && !$invoice->is_opening_balance) {
    // Reverse inventory transactions...
}
```

**Key Change**: Opening balance invoices don't have inventory transactions to reverse.

#### Store/Update Methods

-   Added `is_opening_balance` to validation rules
-   Added `is_opening_balance` to invoice data array
-   Reads from `$request->boolean('is_opening_balance', false)`

---

### 4. UI Changes

#### Create Form (`resources/views/purchase_invoices/create.blade.php`)

-   Added checkbox: "Opening Balance Invoice"
-   Added helpful tooltip explaining that these invoices won't affect inventory
-   Positioned after Cash Account field

#### Edit Form (`resources/views/purchase_invoices/edit.blade.php`)

-   Same checkbox with current value pre-selected
-   Can be changed for draft invoices

#### Show Page (`resources/views/purchase_invoices/show.blade.php`)

-   Displays warning badge: "Opening Balance Invoice"
-   Shows message: "This invoice does NOT affect inventory quantities"

---

## Usage Guide

### Recording Opening Balance Invoices

1. **Navigate to**: `Purchase > Purchase Invoices > Create`

2. **Fill in invoice details**:

    - Date: **January 1, 2026** (or your opening balance date)
    - Vendor: Select the supplier
    - Payment Method: Credit (recommended) or Cash
    - **Check**: ✅ "Opening Balance Invoice" checkbox

3. **Add line items**:

    - Can include inventory items (for accounting purposes)
    - Can include service/expense accounts
    - Quantities and prices as per historical records

4. **Save and Post**:

    - Invoice creates accounting entries (AP liability)
    - **NO inventory transactions created**
    - **NO stock quantity changes**

5. **Allocate Payments**:
    - Can allocate Purchase Payments normally
    - AP balance reduces as payments are made

---

## Behavior Comparison

| Scenario                  | Accounting Entries | Inventory Transactions  | Stock Impact     |
| ------------------------- | ------------------ | ----------------------- | ---------------- |
| **Normal Direct Cash PI** | ✅ Created         | ✅ Created              | ✅ Increases     |
| **Opening Balance PI**    | ✅ Created         | ❌ **NOT Created**      | ❌ **No Impact** |
| **Credit PI (from GRPO)** | ✅ Created         | ❌ Already done at GRPO | ❌ No Impact     |

---

## Accounting Flow

### Opening Balance Invoice (Posted)

**Journal Entry Created**:

```
Debit:  AP UnInvoice (2.1.1.03)     [Reducing un-invoiced liability]
Credit: Utang Dagang (2.1.1.01)    [Creating AP liability]
+ PPN Input (if applicable)
+ Withholding Tax Payable (if applicable)
```

**Inventory Transactions**: ❌ **NONE** (skipped because `is_opening_balance = true`)

**Result**:

-   ✅ AP liability recorded correctly
-   ✅ Can allocate payments
-   ✅ Appears in AP aging reports
-   ✅ **Stock quantities unchanged**

---

## Validation Rules

-   `is_opening_balance`: Optional boolean field
-   Can be set for any invoice type (credit or cash)
-   Can be changed for draft invoices
-   Cannot be changed after posting (would require unposting first)

---

## Business Rules

1. **Opening Balance Flag**:

    - When `is_opening_balance = true`: Skip inventory transactions
    - When `is_opening_balance = false`: Normal behavior (create inventory if direct purchase)

2. **Date Recommendation**:

    - Set invoice date to **January 1, 2026** (or your opening balance date)
    - This ensures proper AP aging calculations

3. **Payment Allocation**:

    - Opening balance invoices can receive payments normally
    - Payments reduce AP liability as expected

4. **Reporting**:
    - Opening balance invoices appear in all standard reports
    - Can be filtered if needed using `is_opening_balance` flag

---

## Testing Checklist

-   [x] Migration runs successfully
-   [x] Checkbox appears in create form
-   [x] Checkbox appears in edit form (for drafts)
-   [x] Opening balance invoice posts without creating inventory transactions
-   [x] Accounting entries created correctly
-   [x] Badge displays on show page
-   [x] Can allocate payments to opening balance invoices
-   [x] Unposting works correctly (no inventory to reverse)

---

## Example Use Case

**Scenario**: Recording opening AP balance of Rp 50,000,000 from Supplier ABC as of January 1, 2026

**Steps**:

1. Create Purchase Invoice

    - Date: 2026-01-01
    - Vendor: Supplier ABC
    - Payment Method: Credit
    - ✅ Check "Opening Balance Invoice"
    - Add line: Description "Opening Balance", Amount Rp 50,000,000

2. Post Invoice

    - ✅ Journal entry created: Debit AP UnInvoice, Credit Utang Dagang
    - ❌ No inventory transactions created
    - ✅ AP liability recorded: Rp 50,000,000

3. Allocate Payment (when payment is made)
    - Create Purchase Payment
    - Allocate to this invoice
    - AP liability reduces

---

## Benefits

1. ✅ **Clean Separation**: Clear distinction between operational and opening balance invoices
2. ✅ **No Double Counting**: Prevents incorrect inventory increases
3. ✅ **Proper Accounting**: AP liability recorded correctly
4. ✅ **Audit Trail**: Flag provides clear audit trail
5. ✅ **Flexible**: Can be used for any historical invoice recording

---

## Future Enhancements (Optional)

1. **Filter in Reports**: Add filter to exclude/include opening balance invoices
2. **Bulk Import**: Support bulk import of opening balance invoices
3. **Validation**: Prevent opening balance invoices from linking to GRPO/PO
4. **Dashboard**: Show opening balance invoice count/amount separately

---

## Related Files

-   Migration: `database/migrations/2026_02_04_151212_add_is_opening_balance_to_purchase_invoices_table.php`
-   Model: `app/Models/Accounting/PurchaseInvoice.php`
-   Controller: `app/Http/Controllers/Accounting/PurchaseInvoiceController.php`
-   Views:
    -   `resources/views/purchase_invoices/create.blade.php`
    -   `resources/views/purchase_invoices/edit.blade.php`
    -   `resources/views/purchase_invoices/show.blade.php`

---

## Summary

The `is_opening_balance` flag provides a clean, explicit way to record Purchase Invoices as opening balances without affecting inventory quantities. This is essential for:

-   System migrations
-   Historical data recording
-   Opening balance setup
-   Maintaining accurate inventory while recording AP liabilities

**Status**: ✅ Implementation Complete and Ready for Use
