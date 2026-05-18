<?php

namespace App\Http\Resources\Api\Customer;

use App\Models\Accounting\SalesInvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SalesInvoiceLine */
class InvoiceLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = $this->amountFromQtyTimesUnitPrice();
        $amount = (float) $this->amount;
        $lineDisc = (float) ($this->discount_amount ?? 0);
        $discount = $lineDisc > 0.00001
            ? round($lineDisc, 2)
            : ($subtotal > $amount ? round($subtotal - $amount, 2) : 0.0);

        $itemParts = array_filter([$this->item_code, $this->item_name]);
        $item = $itemParts !== [] ? implode(' ', $itemParts) : null;

        return [
            'item' => $item ?? $this->description,
            'description' => $this->description,
            'qty' => (float) $this->qty,
            'unit_price' => (float) $this->unit_price,
            'discount_amount' => round($lineDisc, 2),
            'discount_percentage' => (float) ($this->discount_percentage ?? 0),
            'discount' => $discount,
            'total' => $amount,
        ];
    }
}
