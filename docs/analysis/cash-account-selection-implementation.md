# Cash Account Selection Feature - Implementation Summary

**Date**: 2025-12-26  
**Feature**: User-selectable cash account for direct cash purchases  
**Status**: ✅ Implemented

---

## Overview

Previously, the cash account for direct cash purchases was **hardcoded** to `1.1.1.01` (Kas di Tangan). This implementation allows users to select from multiple cash accounts when creating direct cash purchase invoices.

---

## Current Implementation

### 1. **Automatic Selection (Default Behavior)**

**Answer to Question 1**: Yes, the cash account is **automatically selected by the system** by default.

- If no cash account is selected, the system defaults to `1.1.1.01` (Kas di Tangan)
- This ensures backward compatibility with existing invoices
- Non-accounting users don't need to worry about account selection

### 2. **User Selection (New Feature)**

**Answer to Question 2**: Yes, users can now **select a different cash account** if needed.

- Cash account dropdown appears when:
  - `payment_method = 'cash'` AND
  - `is_direct_purchase = 1`
- Available cash accounts:
  - `1.1.1.01` - Kas di Tangan (default)
  - `1.1.1.02` - Kas di Bank - Operasional
  - `1.1.1.03` - Kas di Bank - Investasi
- Users can leave it empty to use the default account

---

## Database Changes

### Migration: `add_cash_account_id_to_purchase_invoices_table`

```php
Schema::table('purchase_invoices', function (Blueprint $table) {
    $table->unsignedBigInteger('cash_account_id')->nullable()->after('is_direct_purchase');
    $table->foreign('cash_account_id')->references('id')->on('accounts')->onDelete('set null');
});
```

**Fields Added**:
- `cash_account_id` (nullable) - Foreign key to `accounts` table

---

## Code Changes

### 1. Model: `PurchaseInvoice`

**Added to `$fillable`**:
```php
'cash_account_id',
```

### 2. Controller: `PurchaseInvoiceController`

#### `create()` Method
- Loads cash accounts from database (accounts with code `1.1.1%`, excluding parent)
- Passes `$cashAccounts` to view

#### `store()` Method
- Added validation: `'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id']`
- Saves `cash_account_id` from request: `'cash_account_id' => $request->input('cash_account_id')`

#### `edit()` Method
- Loads cash accounts (same as `create()`)
- Passes `$cashAccounts` to view

#### `update()` Method
- Added validation: `'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id']`
- Updates `cash_account_id`: `'cash_account_id' => $request->input('cash_account_id')`

#### `postDirectCashPurchase()` Method
**Updated Logic**:
```php
// Use selected cash account, or fallback to default (Kas di Tangan)
$cashAccountId = $invoice->cash_account_id;
if (!$cashAccountId) {
    $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id'); // Default: Kas di Tangan
}
```

**Behavior**:
- If `cash_account_id` is set → Use selected account
- If `cash_account_id` is null → Use default (`1.1.1.01`)

### 3. Views

#### `create.blade.php` & `edit.blade.php`

**Added Cash Account Field**:
```blade
<div class="col-md-4" id="cash_account_field" style="display: none;">
    <div class="form-group row mb-2">
        <label class="col-sm-3 col-form-label">Cash Account</label>
        <div class="col-sm-9">
            <select name="cash_account_id" id="cash_account_id"
                class="form-control form-control-sm select2bs4">
                <option value="">-- Default (Kas di Tangan) --</option>
                @foreach ($cashAccounts ?? [] as $cashAccount)
                    <option value="{{ $cashAccount->id }}"
                        {{ old('cash_account_id', $invoice->cash_account_id ?? '') == $cashAccount->id ? 'selected' : '' }}>
                        {{ $cashAccount->code }} - {{ $cashAccount->name }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">Leave empty to use default cash account</small>
        </div>
    </div>
</div>
```

**JavaScript Toggle Logic**:
```javascript
function toggleCashAccountField() {
    const paymentMethod = $('#payment_method').val();
    const isDirectPurchase = $('#is_direct_purchase').is(':checked');
    
    if (paymentMethod === 'cash' && isDirectPurchase) {
        $('#cash_account_field').show();
    } else {
        $('#cash_account_field').hide();
    }
}

// Initialize on page load
toggleCashAccountField();

// Update on change
$('#payment_method, #is_direct_purchase').on('change', function() {
    toggleCashAccountField();
});
```

**Field Visibility**:
- Hidden by default
- Shown when: `payment_method = 'cash'` AND `is_direct_purchase = checked`
- Hidden when: `payment_method = 'credit'` OR `is_direct_purchase = unchecked`

---

## User Experience

### Scenario 1: Default Behavior (No Selection)

1. User creates Purchase Invoice
2. Sets `payment_method = 'cash'`
3. Checks `is_direct_purchase = 1`
4. **Leaves Cash Account empty** (or doesn't see the field)
5. System uses default: `1.1.1.01` (Kas di Tangan)

**Result**: ✅ Works as before (backward compatible)

---

### Scenario 2: User Selects Cash Account

1. User creates Purchase Invoice
2. Sets `payment_method = 'cash'`
3. Checks `is_direct_purchase = 1`
4. **Cash Account field appears**
5. User selects: `1.1.1.02 - Kas di Bank - Operasional`
6. Posts invoice

**Result**: 
- Journal entry credits: `1.1.1.02 - Kas di Bank - Operasional`
- Instead of default: `1.1.1.01 - Kas di Tangan`

---

## Accounting Flow

### Direct Cash Purchase (with selected cash account)

**When Posted**:
```
Debit:  Inventory Account (from item category)
Credit: Selected Cash Account (or default if not selected)
```

**Example**:
- Invoice: #72250300002
- Cash Account Selected: `1.1.1.02 - Kas di Bank - Operasional`
- Amount: Rp 2,250,000.00

**Journal Entry**:
```
Debit:  1.1.3.01.07 - Persediaan Consumables  Rp 2,250,000.00
Credit: 1.1.1.02 - Kas di Bank - Operasional  Rp 2,250,000.00
```

---

## Benefits

1. ✅ **Flexibility**: Users can choose appropriate cash account for each transaction
2. ✅ **Backward Compatible**: Existing invoices continue to work (default account used)
3. ✅ **User-Friendly**: Field only appears when relevant (cash + direct purchase)
4. ✅ **Optional**: Users can leave it empty to use default
5. ✅ **Future-Proof**: Supports multiple cash accounts (Kas di Tangan, Kas di Bank, etc.)

---

## Testing Checklist

- [x] Migration runs successfully
- [x] Cash account field appears when `payment_method = 'cash'` AND `is_direct_purchase = 1`
- [x] Cash account field hidden when `payment_method = 'credit'` OR `is_direct_purchase = unchecked`
- [x] Default account used when `cash_account_id` is null
- [x] Selected account used when `cash_account_id` is set
- [x] Journal entry credits correct cash account
- [x] Edit form pre-selects saved cash account
- [x] Validation works correctly

---

## Summary

**Question 1**: Is the cash account automatically selected by the system?
- **Answer**: Yes, by default it uses `1.1.1.01` (Kas di Tangan). Users can now override this selection.

**Question 2**: Can users select a different cash account?
- **Answer**: Yes! Users can select from available cash accounts when creating direct cash purchases. The field appears automatically when `payment_method = 'cash'` and `is_direct_purchase = 1`.

**Implementation**: ✅ Complete and ready for use!

