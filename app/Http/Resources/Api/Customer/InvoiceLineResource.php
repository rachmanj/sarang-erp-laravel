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
        $discount = $subtotal > $amount ? round($subtotal - $amount, 2) : 0.0;

        $itemParts = array_filter([$this->item_code, $this->item_name]);
        $item = $itemParts !== [] ? implode(' ', $itemParts) : null;

        return [
            'item' => $item ?? $this->description,
            'description' => $this->description,
            'qty' => (float) $this->qty,
            'unit_price' => (float) $this->unit_price,
            'discount' => $discount,
            'total' => $amount,
        ];
    }
}
