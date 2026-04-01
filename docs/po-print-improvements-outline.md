# Purchase Order Print Improvements – Implementation Outline

This document outlines the exact changes needed to:
1. Redesign the PO print to match the proposed layout (PT Cahaya Sarange Jaya style)
2. Add entity template selection (CV Cahaya Saranghae / PT Cahaya Sarange Jaya)
3. Add discount breakdown to the print output

---

## 1. Route & Controller Changes

### 1.1 Move print from closure to controller

**File:** `routes/web/orders.php`

- **Current:** Inline closure at lines 430–435
- **Change:** Replace with controller method:
  ```php
  Route::get('/{id}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
  ```

### 1.2 Add `print` method to PurchaseOrderController

**File:** `app/Http/Controllers/PurchaseOrderController.php`

```php
public function print(Request $request, $id)
{
    $order = PurchaseOrder::with([
        'lines.inventoryItem',
        'lines.orderUnit.unit',
        'businessPartner',
        'companyEntity',
        'createdBy',
        'currency'
    ])->findOrFail($id);

    $layout = $request->get('layout', 'standard');
    $entitySlug = $request->get('entity', null); // 'cv_saranghae' | 'pt_csj' | null = use PO's company_entity

    $entity = null;
    if ($entitySlug === 'cv_saranghae') {
        $entity = CompanyEntity::where('name', 'CV Cahaya Saranghae')->first();
    } elseif ($entitySlug === 'pt_csj') {
        $entity = CompanyEntity::where('name', 'PT Cahaya Sarange Jaya')->first();
    } else {
        $entity = $order->companyEntity;
    }

    $viewMap = [
        'standard' => 'purchase_orders.print',
        'dotmatrix' => 'purchase_orders.print_dotmatrix',
        'cv_saranghae' => 'purchase_orders.print_cv_saranghae',
        'cv_saranghae_dotmatrix' => 'purchase_orders.print_dotmatrix_cv_saranghae',
        'pt_csj' => 'purchase_orders.print_pt_csj',
        'pt_csj_dotmatrix' => 'purchase_orders.print_dotmatrix_pt_csj',
    ];
    $view = $viewMap[$layout] ?? $viewMap['standard'];

    return view($view, compact('order', 'entity'));
}
```

---

## 2. Show Page – Print Dropdown

**File:** `resources/views/purchase_orders/show.blade.php`

**Current dropdown (lines 33–40):**
- Standard (A4/Laser)
- Dot Matrix

**New dropdown structure:**

```blade
<div class="btn-group mr-1">
    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
        <i class="fas fa-print"></i> Print
    </button>
    <div class="dropdown-menu">
        <h6 class="dropdown-header">Standard (A4)</h6>
        <a class="dropdown-item" href="{{ route('purchase-orders.print', [$order->id, 'layout' => 'standard']) }}" target="_blank">
            <i class="fas fa-file-alt mr-1"></i> Default
        </a>
        <a class="dropdown-item" href="{{ route('purchase-orders.print', [$order->id, 'layout' => 'pt_csj']) }}" target="_blank">
            <i class="fas fa-file-alt mr-1"></i> PT Cahaya Sarange Jaya (A4)
        </a>
        <a class="dropdown-item" href="{{ route('purchase-orders.print', [$order->id, 'layout' => 'cv_saranghae']) }}" target="_blank">
            <i class="fas fa-file-alt mr-1"></i> CV Cahaya Saranghae (A4)
        </a>
        <div class="dropdown-divider"></div>
        <h6 class="dropdown-header">Dot Matrix</h6>
        <a class="dropdown-item" href="{{ route('purchase-orders.print', [$order->id, 'layout' => 'dotmatrix']) }}" target="_blank">
            <i class="fas fa-print mr-1"></i> Default
        </a>
        <a class="dropdown-item" href="{{ route('purchase-orders.print', [$order->id, 'layout' => 'pt_csj_dotmatrix']) }}" target="_blank">
            <i class="fas fa-print mr-1"></i> PT Cahaya Sarange Jaya
        </a>
        <a class="dropdown-item" href="{{ route('purchase-orders.print', [$order->id, 'layout' => 'cv_saranghae_dotmatrix']) }}" target="_blank">
            <i class="fas fa-print mr-1"></i> CV Cahaya Saranghae
        </a>
    </div>
</div>
```

---

## 3. New Print Template – PT Cahaya Sarange Jaya (Proposed Design)

**File:** `resources/views/purchase_orders/print_pt_csj.blade.php` (new)

Design based on the proposed PO layout (reference: `docs/proposed_po.jpg`).

### 3.1 Visual layout structure

```
┌─────────────────────────────────────────────────────────────────────────┐
│ [Logo]  Cahaya Sarange Jaya          Office Balikpapan:                  │
│         PT CAHAYA SARANGE JAYA       Mal Fantasi Balikpapan Baru...     │
│         General Supplier             Office Jakarta:                     │
│                                      Jl. Raya Cilandak KKO...           │
├─────────────────────────────────────────────────────────────────────────┤
│              ┌─────────────────────────────┐                              │
│              │     PURCHASE ORDER          │  (gray bg)                   │
│              └─────────────────────────────┘                              │
│                    No : 2601001.P                                        │
├─────────────────────────────────────────────────────────────────────────┤
│ Date: 14 Jan 2026  │  Curr: IDR    │  Refer:                             │
│ Payment Terms: Cash │  (empty)     │  Delivery Date:                     │
├──────────────────────────────┬──────────────────────────────────────────┤
│ ORDER TO:                    │ SHIP TO:                                   │
│ Vendor: PT SURYAMAS...        │ PT. CAHAYA SARANGE JAYA                   │
│ Address: Jl. Raya...         │ Ruko Puri Mal Fantasi...                  │
│ Contact:                     │ Contact: Tauvik                           │
│ Attn:                        │ Attn: +62 811-5403-108                    │
├──────────────────────────────┴──────────────────────────────────────────┤
│ NO │ PART NO. │ ITEM DESCRIPTION    │ UOM │ QTY │ UNIT PRICE │ TOTAL    │
│ 1  │          │ KAIGO PENETRATING.. │ EA  │ 48  │ 31.000,00  │ 1.488.000 │
├─────────────────────────────────────────────────────────────────────────┤
│ IMPORTANT NOTES:              │ Total:           2.928.000,00             │
│ •                             │ DPP (11/12):     2.684.000,00             │
│ •                             │ Tax 12%:          322.080,00             │
│ Say: Tiga juta... rupiah      │ Total After Tax: 3.250.080,00             │
│ Vendor Confirmation           │ Grand Total:     3.250.080,00             │
│ _________________________     │                                          │
│                               │ Prepared by    Approved by               │
│                               │ (signature)     (signature)              │
│                               │ Nurita          Tauvik                   │
└───────────────────────────────┴──────────────────────────────────────────┘
```

### 3.2 Header section

- **Left:** Logo (`logo_pt_csj.png`), company name “Cahaya Sarange Jaya”, “PT CAHAYA SARANGE JAYA”, “General Supplier”
- **Right:** Multi-office addresses:
  - Office Balikpapan: from `ErpParameter::get('pt_csj_office_balikpapan', '')` or `$entity->address`
  - Office Jakarta: from `ErpParameter::get('pt_csj_office_jakarta', '')`

### 3.3 Title block

- Gray background rectangle: “PURCHASE ORDER”
- Below: “No : {{ $order->order_no }}”

### 3.4 Order information table (2 rows × 3 cols)

| Row 1 | Date | Curr | Refer |
| Row 2 | Payment Terms | (empty) | Delivery Date |

### 3.5 ORDER TO / SHIP TO (two columns)

**ORDER TO (left):**
- Vendor: `$order->businessPartner->name`
- Address: `$order->businessPartner->address`
- Contact: (from BP or empty)
- Attn: (from BP or empty)

**SHIP TO (right):**
- Company name from selected entity
- Ship-to address (entity address or warehouse address)
- Contact: from entity or ERP param
- Attn: from entity or ERP param

### 3.6 Items table

| NO | PART NO. | ITEM DESCRIPTION | UOM | QTY | UNIT PRICE | TOTAL PRICE |

- UOM: `$line->orderUnit->unit->code ?? $line->inventoryItem->unit_of_measure ?? '-'`
- TOTAL PRICE: `$line->amount` (or show `$line->net_amount` if discount present; see discount section)

### 3.7 Footer – left side

- IMPORTANT NOTES: (2 bullet lines, from `$order->notes` or `$order->terms_conditions`)
- **Say:** Total in words (Indonesian terbilang)
- Vendor Confirmation: signature line

### 3.8 Footer – right side (financial summary)

- Total: subtotal before header discount
- DPP (11/12): base for tax (subtotal after line discounts, before VAT)
- Tax 12%: VAT amount
- Total After Tax: DPP + Tax
- Grand Total: `$order->total_amount`

### 3.9 Signature blocks

- Prepared by: `$order->createdBy->name`
- Approved by: from approval workflow or empty

### 3.10 Discount handling

When any line has discount or header has discount:

- **Line table:** Add optional columns: Disc %, Disc Amt, Net Amount (or show in TOTAL PRICE the net amount)
- **Financial summary:**
  - Total (sum of line amounts before header discount)
  - Line Discounts: sum of `$line->discount_amount`
  - Header Discount: `$order->discount_amount`
  - Subtotal: Total - Line Discounts - Header Discount (or match current calculation)
  - DPP, Tax, Grand Total

---

## 4. ERP Parameters for Multi-Office

**File:** `database/migrations/xxxx_add_pt_csj_office_params.php` (new)

Add parameters:

- `pt_csj_office_balikpapan` – e.g. “Mal Fantasi Balikpapan Baru, Ruko Puri Blok A35, Balikpapan Baru, Kal-Tim, 76114”
- `pt_csj_office_jakarta` – e.g. “Jl. Raya Cilandak KKO, Komplek Vico Kav.12, Jakarta Selatan, 12560”
- `pt_csj_tagline` – e.g. “General Supplier”

Alternatively, store in `company_entities` or `letterhead_meta` JSON.

---

## 5. Helper for Total in Words

**Option A:** Reuse `CashExpenseController::convertToWords` – extract to a helper/service.

**File:** `app/Helpers/NumberToWordsHelper.php` (new)

```php
<?php
namespace App\Helpers;

class NumberToWordsHelper
{
    public static function toIndonesianRupiah(float $number): string
    {
        // Move logic from CashExpenseController::convertToWords
        // Return e.g. "Tiga juta dua ratus lima puluh ribu delapan puluh rupiah"
    }
}
```

**Option B:** Add `convertToWords` to `PurchaseOrderController` (or a trait) and pass `$terbilang` to the view.

---

## 6. Template Files to Create/Modify

| File | Action |
|------|--------|
| `purchase_orders/print_pt_csj.blade.php` | **Create** – PT design (proposed layout) |
| `purchase_orders/print_dotmatrix_pt_csj.blade.php` | **Create** – PT dot matrix variant |
| `purchase_orders/print_cv_saranghae.blade.php` | **Create** – CV entity template |
| `purchase_orders/print_dotmatrix_cv_saranghae.blade.php` | **Create** – CV dot matrix |
| `purchase_orders/print.blade.php` | **Modify** – Add discount columns when applicable |
| `purchase_orders/print_dotmatrix.blade.php` | **Modify** – Add discount columns when applicable |

---

## 7. Discount Logic in Templates

**Condition:** Show discount columns when `$order->discount_amount > 0` OR any `$line->discount_amount > 0`.

**Line table changes:**
- Add columns: Disc %, Disc Amt (optional), use Net Amount or Amount for TOTAL PRICE
- Use `$line->net_amount` when discount exists, else `$line->amount`

**Footer totals (computed in view or controller):**
```php
// Subtotal = sum of line amounts (each line.amount already includes line discount, VAT, WTax)
$subtotalBeforeHeaderDiscount = $order->lines->sum('amount');
$totalLineDiscount = $order->lines->sum('discount_amount');
$headerDiscount = $order->discount_amount ?? 0;
$grandTotal = $order->total_amount; // = subtotalBeforeHeaderDiscount - headerDiscount

// DPP = taxable base for VAT (sum of net_amount for lines, or use simplified logic)
$dpp = $order->lines->sum('net_amount') - $headerDiscount; // net_amount = before VAT
$vatAmount = $order->lines->sum(fn($l) => $l->net_amount * ($l->vat_rate / 100));
$totalAfterTax = $grandTotal; // already includes VAT in line amounts
```

Note: `line.amount` = net_amount + vat_amount - wtax_amount. `order.total_amount` = sum(line.amount) - header_discount.

---

## 8. CompanyEntity / BusinessPartner Data

Ensure:

- `CompanyEntity` has: `name`, `address`, `phone`, `email`, `logo_path`
- `BusinessPartner` has: `name`, `address`, and contact fields for ORDER TO
- PO has `company_entity_id` for default entity

---

## 9. Implementation Order

1. Add `print` method to `PurchaseOrderController` and update route
2. Create `NumberToWordsHelper` (or equivalent)
3. Add ERP parameters for PT offices (if needed)
4. Create `print_pt_csj.blade.php` with full proposed layout
5. Create `print_cv_saranghae.blade.php` (reuse CV logic from Delivery Order)
6. Create dot matrix variants
7. Update `print.blade.php` and `print_dotmatrix.blade.php` with discount columns
8. Update show page dropdown with entity options
9. Test all layouts with and without discounts

---

## 10. CSS / Styling for Proposed Design

- Gray section headers: `background: #e0e0e0` or `#d0d0d0`
- Blue accent for logo/company name: `color: #0066cc` or similar
- Bordered cells: `border: 1px solid #000`
- Font: Arial or similar sans-serif
- Layout: flex/grid for ORDER TO / SHIP TO columns

---

## 11. Route Parameter Compatibility

Current route: `Route::get('/{id}/print', ...)`  
Ensure `{id}` works with both `$order->id` and explicit ID. Delivery Order uses `{deliveryOrder}` (model binding). For PO, keep `{id}` and use `findOrFail` for consistency with existing closure.
