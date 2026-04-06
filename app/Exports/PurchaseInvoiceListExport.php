<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class PurchaseInvoiceListExport implements FromArray
{
    /**
     * @param  array<int, array<int, float|int|string|null>>  $rows
     */
    public function __construct(
        private array $rows
    ) {}

    /**
     * @return array<int, array<int, float|int|string|null>>
     */
    public function array(): array
    {
        return $this->rows;
    }
}
