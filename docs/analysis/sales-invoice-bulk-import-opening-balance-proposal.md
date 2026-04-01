# Sales Invoice Bulk Import for Opening Balance - Updated Proposal

**Date**: 2026-02-04  
**Status**: 📋 PROPOSAL - Updated per User Feedback  
**Purpose**: Bulk import Sales Invoices as opening balance with Retained Earnings accounting treatment

---

## Updated Understanding (After User Feedback)

### Simplified Requirement:

1. **Bulk Upload**: Upload multiple Sales Invoices via Excel file
2. **Date**: All invoices dated **January 1, 2026**
3. **Opening Balance Flag**: All invoices marked as `is_opening_balance = true`
4. **Accounting Treatment** (when `is_opening_balance = true`):
   - **Debit**: Piutang Dagang (AR Account - 1.1.2.01) = Revenue Total + VAT Total
   - **Credit**: Saldo Awal Laba Ditahan (Retained Earnings Opening Balance - 3.3.1) = Revenue Total
   - **Credit**: PPN Keluaran (VAT Output - 2.1.2, if applicable) = VAT Total

### Key Change:

**Previous Implementation** (when `is_opening_balance = true`):
```
Debit:  Piutang Dagang (AR)
Credit: Revenue Accounts (from invoice lines)  ❌ Changed
Credit: PPN Keluaran (VAT Output, if applicable)
```

**Updated Implementation** (when `is_opening_balance = true`):
```
Debit:  Piutang Dagang (AR)
Credit: Saldo Awal Laba Ditahan (3.3.1)  ✅ Fixed account
Credit: PPN Keluaran (VAT Output, if applicable)
```

**Why This Makes Sense**:
- Opening balances represent historical AR that was already part of retained earnings
- Not new revenue - revenue was recognized in previous periods
- Crediting Retained Earnings Opening Balance is the correct accounting treatment
- **No need for new flag** - modify existing `is_opening_balance` logic

---

## Implementation Plan

### Phase 1: Update Posting Logic ✅ COMPLETE

**File**: `app/Http/Controllers/Accounting/SalesInvoiceController.php`

**Change**: Modified `post()` method to credit Retained Earnings (3.3.1) instead of Revenue accounts when `is_opening_balance = true`.

**Journal Entry**:
```php
// Debit AR Account
Debit:  Piutang Dagang (1.1.2.01) = revenueTotal + ppnTotal

// Credit Retained Earnings Opening Balance
Credit: Saldo Awal Laba Ditahan (3.3.1) = revenueTotal

// Credit VAT Output (if applicable)
Credit: PPN Keluaran (2.1.2) = ppnTotal
```

---

### Phase 2: Excel Import Feature (To Be Implemented)

#### Excel File Format (Simplified):

| Column | Description | Required | Example |
|--------|-------------|----------|---------|
| `customer_code` | Customer code or name | **Yes** | "CUST001" or "PT ABC" |
| `total_amount` | Total invoice amount (including VAT) | **Yes** | "55000000" |
| `vat_amount` | VAT amount (if applicable) | No | "5000000" |
| `description` | Invoice description | No | "Opening Balance AR" |

**Defaults Applied**:
- Date: **2026-01-01** (fixed)
- Company Entity: Default entity
- Status: **Draft** (user posts manually)
- `is_opening_balance`: **true** (fixed)
- Terms: 30 days (default)

**Calculation**:
- `revenueTotal` = `total_amount` - `vat_amount` (or calculated from total_amount if VAT rate provided)
- `ppnTotal` = `vat_amount` (if provided) or calculated

---

### Phase 3: Import Service Structure

**Service**: `app/Services/Import/SalesInvoiceImportService.php`

**Features**:
1. Read Excel file (using maatwebsite/excel package)
2. Validate customer codes exist
3. Validate amounts > 0
4. Create Sales Invoices in bulk
5. Set `is_opening_balance = true` for all
6. Auto-generate invoice numbers
7. Return import summary with errors/warnings

**Validation**:
- Customer must exist in `business_partners` table (partner_type = 'customer')
- Total amount > 0
- VAT amount >= 0 and <= total_amount
- Date defaults to 2026-01-01

---

### Phase 4: Import Controller & UI

**Controller**: `app/Http/Controllers/SalesInvoiceImportController.php`

**Endpoints**:
- `GET /sales-invoices/import` - Show import page
- `GET /sales-invoices/import/template` - Download Excel template
- `POST /sales-invoices/import/validate` - Validate file (preview)
- `POST /sales-invoices/import` - Execute import

**UI**: `resources/views/sales_invoices/import/index.blade.php`
- File upload form
- Template download link
- Validation results display
- Import progress/results

---

## Example: Import Result

### Excel Input:
```
customer_code | total_amount | vat_amount | description
--------------|--------------|------------|------------------
CUST001       | 55000000     | 5000000    | Opening Balance AR
CUST002       | 82500000     | 7500000    | Opening Balance AR
```

### Created Invoices:

**Invoice 1** (CUST001):
- Date: 2026-01-01
- Total: Rp 55,000,000
- `is_opening_balance`: true
- **Journal** (when posted):
  - Debit: Piutang Dagang Rp 55,000,000
  - Credit: Saldo Awal Laba Ditahan Rp 50,000,000
  - Credit: PPN Keluaran Rp 5,000,000

**Invoice 2** (CUST002):
- Date: 2026-01-01
- Total: Rp 82,500,000
- `is_opening_balance`: true
- **Journal** (when posted):
  - Debit: Piutang Dagang Rp 82,500,000
  - Credit: Saldo Awal Laba Ditahan Rp 75,000,000
  - Credit: PPN Keluaran Rp 7,500,000

---

## Updated Accounting Flow

### Opening Balance Invoice (`is_opening_balance = true`)

**Journal Entry**:
```
Debit:  Piutang Dagang (1.1.2.01)        = Revenue + VAT
Credit: Saldo Awal Laba Ditahan (3.3.1) = Revenue
Credit: PPN Keluaran (2.1.2)             = VAT (if applicable)
```

**Accounting Equation**:
```
Assets (AR) ↑ = Equity (Retained Earnings) ↑ + Liability (VAT) ↑
```

---

### Regular Invoice (`is_opening_balance = false`)

**Journal Entry**:
```
Debit:  AR UnInvoice (1.1.2.04)          = Revenue + VAT
Credit: Piutang Dagang (1.1.2.01)        = Revenue
Credit: PPN Keluaran (2.1.2)             = VAT (if applicable)
```

**Note**: Revenue recognition handled by Delivery Order, not Sales Invoice.

---

## Implementation Status

### ✅ Completed:
1. Updated posting logic to credit Retained Earnings (3.3.1) when `is_opening_balance = true`
2. Removed Revenue account grouping logic
3. Simplified to fixed Retained Earnings account

### 📋 To Be Implemented:
1. Excel import service
2. Import controller
3. Import UI
4. Template generation
5. Validation logic
6. Bulk post feature (optional)

---

## Next Steps

1. ✅ **Posting Logic Updated** - Ready for testing
2. 📋 **Excel Import Feature** - Awaiting implementation approval
3. 📋 **Bulk Post Feature** - Optional enhancement

---

## Summary

**Updated Approach**:
- ✅ **No new flag needed** - Use existing `is_opening_balance`
- ✅ **Simplified logic** - Credit fixed Retained Earnings account (3.3.1)
- ✅ **Correct accounting** - Opening balances go to Retained Earnings, not Revenue
- ✅ **Ready for bulk import** - Posting logic ready, import feature to be built

**Status**: Posting logic updated ✅ | Import feature pending 📋
