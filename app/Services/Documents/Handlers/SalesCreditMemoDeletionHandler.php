<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\SalesCreditMemo;
use App\Services\Documents\DocumentType;
use Illuminate\Database\Eloquent\Model;

class SalesCreditMemoDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function type(): string
    {
        return DocumentType::SALES_CREDIT_MEMO;
    }

    public function children(Model $document): array
    {
        return [];
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var SalesCreditMemo $document */
        if ($document->status === 'posted') {
            $this->support->reverseJournalsFor(['sales_credit_memo'], (int) $document->id);
            $this->support->deleteTaxTransactions('sales_credit_memo', (int) $document->id);
        }

        $document->lines()->delete();
        $this->support->clearDocumentRelationships(SalesCreditMemo::class, (int) $document->id);
        $document->delete();
    }
}
