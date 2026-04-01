# Sales Invoice Opening Balance - Restated Requirements & Implementation

**Date**: 2026-02-04  
**Status**: ✅ IMPLEMENTED  
**Purpose**: Restate simplified requirements and document implementation

---

## Restated Requirements

### Simplified Approach:

**When `is_opening_balance = true`**, the journal entry will be:

```
Debit:  Piutang Dagang (1.1.2.01)                    = Revenue Total + VAT Total
Credit: Saldo Awal Laba Ditahan (3.3.1)              = Revenue Total
Credit: PPN Keluaran (2.1.2)                         = VAT Total (if applicable)
```

### Key Points:

1. ✅ **No new flag needed** - Use existing `is_opening_balance` flag
2. ✅ **Fixed account** - Always credit Retained Earnings (3.3.1), not Revenue accounts
3. ✅ **Simplified logic** - No need to group by Revenue accounts from lines
4. ✅ **Correct accounting** - Opening balances represent historical AR already in retained earnings

---

## Implementation Changes

### ✅ Updated Posting Logic

**File**: `app/Http/Controllers/Accounting/SalesInvoiceController.php` (Line 239-275)

**Before** (Credited Revenue Accounts):
```php
// Credit Revenue Accounts (from invoice lines)
foreach ($revenueByAccount as $key => $data) {
    $lines[] = [
        'account_id' => $data['account_id'],  // Revenue account from line
        'debit' => 0,
        'credit' => $data['amount'],
        // ...
    ];
}
```

**After** (Credits Retained Earnings):
```php
// Credit Retained Earnings Opening Balance (3.3.1)
$retainedEarningsAccountId = (int) DB::table('accounts')->where('code', '3.3.1')->value('id');

$lines[] = [
    'account_id' => $retainedEarningsAccountId,  // Fixed: 3.3.1
    'debit' => 0,
    'credit' => $revenueTotal,  // Total revenue from all lines
    'memo' => 'Saldo Awal Laba Ditahan - Opening Balance',
];
```

---

## Journal Entry Examples

### Example 1: Opening Balance Invoice (No VAT)

**Invoice**:
- Customer: PT ABC
- Total Amount: Rp 100,000,000
- VAT: None
- `is_opening_balance`: true

**Journal Entry** (when posted):
```
Date: 2026-01-01
Description: Post AR Invoice #[id]

Line 1:
  Debit:  1.1.2.01 - Piutang Dagang        Rp 100,000,000.00
  Credit: 0

Line 2:
  Debit:  0
  Credit: 3.3.1 - Saldo Awal Laba Ditahan   Rp 100,000,000.00
```

---

### Example 2: Opening Balance Invoice (With VAT)

**Invoice**:
- Customer: PT XYZ
- Total Amount: Rp 111,000,000
- Revenue: Rp 100,000,000
- VAT (11%): Rp 11,000,000
- `is_opening_balance`: true

**Journal Entry** (when posted):
```
Date: 2026-01-01
Description: Post AR Invoice #[id]

Line 1:
  Debit:  1.1.2.01 - Piutang Dagang        Rp 111,000,000.00
  Credit: 0

Line 2:
  Debit:  0
  Credit: 3.3.1 - Saldo Awal Laba Ditahan   Rp 100,000,000.00

Line 3:
  Debit:  0
  Credit: 2.1.2 - PPN Keluaran              Rp 11,000,000.00
```

---

## Comparison: Opening Balance vs Regular Invoice

| Aspect | Opening Balance Invoice | Regular Invoice |
|--------|------------------------|-----------------|
| **Flag** | `is_opening_balance = true` | `is_opening_balance = false` |
| **AR Entry** | Direct: Debit AR | Via AR UnInvoice: Debit AR UnInvoice, Credit AR |
| **Credit Account** | **Retained Earnings (3.3.1)** | Revenue (recognized at DO, not SI) |
| **VAT** | Credit PPN Keluaran | Credit PPN Keluaran |
| **Accounting Logic** | Historical balance | Operational transaction |

---

## Next Steps: Excel Import Feature

### Proposed Excel Format:

| customer_code | total_amount | vat_amount | description |
|---------------|--------------|------------|-------------|
| CUST001 | 55000000 | 5000000 | Opening Balance AR |
| CUST002 | 82500000 | 7500000 | Opening Balance AR |

**Defaults**:
- Date: 2026-01-01
- `is_opening_balance`: true
- Status: Draft (user posts manually)
- Company Entity: Default entity

**Result**: All imported invoices will use Retained Earnings (3.3.1) when posted.

---

## Summary

✅ **Posting Logic Updated**: 
- Opening balance invoices now credit Retained Earnings (3.3.1) instead of Revenue accounts
- Simplified logic - no Revenue account grouping needed
- Correct accounting treatment for historical opening balances

📋 **Excel Import Feature**: 
- To be implemented next
- Will create invoices with `is_opening_balance = true`
- All will post to Retained Earnings when posted

**Status**: ✅ Posting logic complete | 📋 Import feature pending
