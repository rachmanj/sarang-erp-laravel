<?php

namespace Tests\Unit;

use App\Models\InventoryTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryTransactionReferenceDocumentLabelsTest extends TestCase
{
    #[Test]
    public function hydrate_reference_document_labels_does_not_error_on_empty_page(): void
    {
        $paginator = new LengthAwarePaginator(collect(), 0, 10);

        InventoryTransaction::hydrateReferenceDocumentLabels($paginator);

        $this->assertCount(0, $paginator->getCollection());
    }
}
