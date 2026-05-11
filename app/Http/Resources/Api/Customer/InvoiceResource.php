<?php

namespace App\Http\Resources\Api\Customer;

use App\Models\Accounting\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SalesInvoice */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoice_no' => $this->invoice_no,
            'date' => $this->date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'terms_days' => $this->terms_days,
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'exchange_rate' => $this->exchange_rate !== null ? (float) $this->exchange_rate : null,
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'code' => $this->currency?->code,
                    'symbol' => $this->currency?->symbol ?? $this->currency?->code,
                    'name' => $this->currency?->name,
                ];
            }),
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
