<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Services\Documents\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchasePaymentDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function type(): string
    {
        return DocumentType::PURCHASE_PAYMENT;
    }

    public function children(Model $document): array
    {
        return [];
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var PurchasePayment $document */
        if ($document->status === 'posted') {
            $this->support->reverseJournalsFor(['purchase_payment'], (int) $document->id);
        }

        $invoiceIds = DB::table('purchase_payment_allocations')
            ->where('payment_id', $document->id)
            ->pluck('invoice_id')
            ->all();

        DB::table('purchase_payment_allocations')->where('payment_id', $document->id)->delete();
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(PurchasePayment::class, (int) $document->id);
        $document->delete();

        foreach ($invoiceIds as $invoiceId) {
            $invoice = PurchaseInvoice::query()->find($invoiceId);
            if (! $invoice || $invoice->status !== 'posted') {
                continue;
            }

            $allocated = (float) DB::table('purchase_payment_allocations')->where('invoice_id', $invoiceId)->sum('amount');
            if ($allocated + 0.009 < (float) $invoice->total_amount
                && $invoice->closure_status === 'closed'
                && $invoice->closed_by_document_type === 'purchase_payment') {
                $invoice->forceFill([
                    'closure_status' => 'open',
                    'closed_by_document_type' => null,
                    'closed_by_document_id' => null,
                    'closed_at' => null,
                    'closed_by_user_id' => null,
                ])->save();
            }
        }
    }
}
