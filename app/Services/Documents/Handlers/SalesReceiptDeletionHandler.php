<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Services\Documents\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesReceiptDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function type(): string
    {
        return DocumentType::SALES_RECEIPT;
    }

    public function children(Model $document): array
    {
        return [];
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var SalesReceipt $document */
        if ($document->status === 'posted') {
            $this->support->reverseJournalsFor(['sales_receipt'], (int) $document->id);
        }

        $invoiceIds = DB::table('sales_receipt_allocations')
            ->where('receipt_id', $document->id)
            ->pluck('invoice_id')
            ->all();

        DB::table('sales_receipt_allocations')->where('receipt_id', $document->id)->delete();
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(SalesReceipt::class, (int) $document->id);
        $document->delete();

        foreach ($invoiceIds as $invoiceId) {
            $invoice = SalesInvoice::query()->find($invoiceId);
            if (! $invoice || $invoice->status !== 'posted') {
                continue;
            }

            $allocated = (float) DB::table('sales_receipt_allocations')->where('invoice_id', $invoiceId)->sum('amount');
            if ($allocated + 0.01 < (float) $invoice->total_amount
                && $invoice->closure_status === 'closed'
                && $invoice->closed_by_document_type === 'sales_receipt') {
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
