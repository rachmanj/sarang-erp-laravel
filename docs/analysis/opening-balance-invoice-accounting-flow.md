# Opening Balance Invoice Accounting Flow

**Date**: 2026-02-04  
**Status**: ✅ COMPLETE  
**Purpose**: Document the accounting flow for opening balance Purchase and Sales Invoices

---

## Overview

When Purchase Invoices (PI) or Sales Invoices (SI) are flagged as `is_opening_balance = true`, they use a different accounting flow that:
- ✅ Uses accounts from invoice lines directly (not AP UnInvoice/AR UnInvoice)
- ✅ Preserves project/department dimensions from lines
- ✅ Creates proper AP/AR liability without intermediate accounts

---

## Purchase Invoice - Opening Balance Flow

### Credit Purchase Invoice (Opening Balance)

**Journal Entry**:
```
Debit:  Account from Line 1 (e.g., 1.1.3.01 - Persediaan)
Debit:  Account from Line 2 (e.g., 5.1.1.01 - Expense)
Credit: Utang Dagang (2.1.1.01)
+ PPN Input (if applicable)
+ Withholding Tax Payable (if applicable)
```

**Code Location**: `PurchaseInvoiceController::postCreditPurchase()` (Line 762)

**Key Logic**:
- Groups line items by `account_id`
- Creates debit entries for each account used in lines
- Preserves `project_id` and `dept_id` from lines
- Credits Utang Dagang (AP liability)

### Direct Cash Purchase Invoice (Opening Balance)

**Journal Entry**:
```
Debit:  Account from Line 1 (selected account)
Debit:  Account from Line 2 (selected account)
Credit: Cash Account (1.1.1.01 - Kas di Tangan)
+ PPN Input (if applicable)
+ Withholding Tax Payable (if applicable)
```

**Code Location**: `PurchaseInvoiceController::postDirectCashPurchase()` (Line 668)

**Key Logic**:
- Groups line items by `account_id`, `project_id`, and `dept_id`
- Creates debit entries preserving dimensions
- Credits Cash Account

---

## Sales Invoice - Opening Balance Flow

### Sales Invoice (Opening Balance)

**Journal Entry**:
```
Debit:  Piutang Dagang (1.1.2.01)
Credit: Revenue Account from Line 1 (e.g., 4.1.1.01 - Penjualan)
Credit: Revenue Account from Line 2 (e.g., 4.1.2.01 - Service Revenue)
+ PPN Keluaran (if applicable)
```

**Code Location**: `SalesInvoiceController::post()` (Line 239)

**Key Logic**:
- Groups line items by `account_id`, `project_id`, and `dept_id`
- Debits Piutang Dagang (AR receivable)
- Credits Revenue Accounts from lines (preserving dimensions)

---

## Comparison: Normal vs Opening Balance

### Purchase Invoice

| Flow Type | Debit Account | Credit Account | Uses Line Accounts? |
|-----------|--------------|---------------|---------------------|
| **Normal Credit** | AP UnInvoice (2.1.1.03) | Utang Dagang (2.1.1.01) | ❌ No |
| **Opening Balance Credit** | Line Accounts | Utang Dagang (2.1.1.01) | ✅ Yes |
| **Normal Direct Cash** | Inventory Account (from item) | Cash Account | ❌ No (uses item category) |
| **Opening Balance Direct Cash** | Line Accounts | Cash Account | ✅ Yes |

### Sales Invoice

| Flow Type | Debit Account | Credit Account | Uses Line Accounts? |
|-----------|--------------|---------------|---------------------|
| **Normal** | AR UnInvoice (1.1.2.04) | Piutang Dagang (1.1.2.01) | ❌ No |
| **Opening Balance** | Piutang Dagang (1.1.2.01) | Revenue Accounts (from lines) | ✅ Yes |

---

## Benefits

1. ✅ **Direct Account Mapping**: Uses accounts selected on invoice lines
2. ✅ **Multi-Account Support**: Supports multiple accounts per invoice
3. ✅ **Dimension Preservation**: Preserves project/department from lines
4. ✅ **No Intermediate Accounts**: Skips AP UnInvoice/AR UnInvoice for opening balances
5. ✅ **Consistent Logic**: Both PI and SI use same pattern

---

## Example Journal Entries

### Example 1: Opening Balance Purchase Invoice (Credit)

**Invoice**:
- Date: 2026-01-01
- Vendor: Supplier ABC
- Payment Method: Credit
- ✅ Opening Balance: Yes

**Lines**:
- Line 1: Account 1.1.3.01 (Persediaan), Amount Rp 30,000,000, Project: P001, Dept: D001
- Line 2: Account 5.1.1.01 (Expense), Amount Rp 20,000,000, Project: P001, Dept: D002

**Journal Entry Created**:
```
Debit:  1.1.3.01 - Persediaan Barang Dagangan    Rp 30,000,000  [Project: P001, Dept: D001]
Debit:  5.1.1.01 - Beban Operasional             Rp 20,000,000  [Project: P001, Dept: D002]
Credit: 2.1.1.01 - Utang Dagang                  Rp 50,000,000
```

### Example 2: Opening Balance Sales Invoice

**Invoice**:
- Date: 2026-01-01
- Customer: Customer XYZ
- ✅ Opening Balance: Yes

**Lines**:
- Line 1: Account 4.1.1.01 (Penjualan), Amount Rp 40,000,000, Project: P001, Dept: D001
- Line 2: Account 4.1.2.01 (Service Revenue), Amount Rp 10,000,000, Project: P001, Dept: D001

**Journal Entry Created**:
```
Debit:  1.1.2.01 - Piutang Dagang                Rp 50,000,000
Credit: 4.1.1.01 - Penjualan                     Rp 40,000,000  [Project: P001, Dept: D001]
Credit: 4.1.2.01 - Service Revenue               Rp 10,000,000  [Project: P001, Dept: D001]
```

---

## Implementation Details

### Purchase Invoice

**File**: `app/Http/Controllers/Accounting/PurchaseInvoiceController.php`

**Methods**:
- `postCreditPurchase()`: Handles credit purchase invoices
  - Checks `$invoice->is_opening_balance`
  - Groups lines by account_id
  - Creates debit entries for each account
- `postDirectCashPurchase()`: Handles direct cash purchase invoices
  - Checks `$invoice->is_opening_balance`
  - Groups lines by account_id, project_id, dept_id
  - Creates debit entries preserving dimensions

### Sales Invoice

**File**: `app/Http/Controllers/Accounting/SalesInvoiceController.php`

**Method**: `post()`
- Checks `$invoice->is_opening_balance`
- Groups lines by account_id, project_id, dept_id
- Creates credit entries for revenue accounts preserving dimensions
- Debits Piutang Dagang

---

## Key Differences from Normal Flow

### Purchase Invoice

**Normal Credit Flow**:
- Uses AP UnInvoice as intermediate account
- Assumes inventory was already received (via GRPO)
- AP UnInvoice reduces, Utang Dagang increases

**Opening Balance Flow**:
- Uses accounts directly from invoice lines
- No intermediate account
- Direct debit to expense/inventory accounts
- Utang Dagang increases

### Sales Invoice

**Normal Flow**:
- Uses AR UnInvoice as intermediate account
- Assumes revenue was already recognized (via Delivery Order)
- AR UnInvoice reduces, Piutang Dagang increases

**Opening Balance Flow**:
- Uses revenue accounts directly from invoice lines
- No intermediate account
- Direct credit to revenue accounts
- Piutang Dagang increases

---

## Testing Checklist

- [x] Purchase Invoice (Credit) - Opening Balance uses line accounts
- [x] Purchase Invoice (Direct Cash) - Opening Balance uses line accounts
- [x] Sales Invoice - Opening Balance uses line accounts
- [x] Project/department dimensions preserved
- [x] Multiple accounts grouped correctly
- [x] Taxes handled correctly (PPN, Withholding)
- [x] Normal invoices still use AP UnInvoice/AR UnInvoice flow

---

## Summary

Both Purchase Invoice and Sales Invoice now correctly use accounts from invoice lines when flagged as opening balance, providing:
- Direct account mapping
- Multi-dimensional accounting support
- Consistent behavior across both invoice types
- Proper AP/AR liability recording without intermediate accounts

**Status**: ✅ Implementation Complete and Verified
