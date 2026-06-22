<?php

namespace App\Services\Documents\Handlers;

use App\Models\SalesQuotation;
use App\Services\Documents\DocumentType;
use Illuminate\Database\Eloquent\Model;

class SalesQuotationDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function type(): string
    {
        return DocumentType::SALES_QUOTATION;
    }

    public function children(Model $document): array
    {
        /** @var SalesQuotation $document */
        $children = [];

        if ($document->converted_to_sales_order_id) {
            $children[] = $this->support->childRef(
                DocumentType::SALES_ORDER,
                (int) $document->converted_to_sales_order_id
            );
        }

        return $this->uniqueChildren($children);
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var SalesQuotation $document */
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(SalesQuotation::class, (int) $document->id);
        $document->delete();
    }
}
