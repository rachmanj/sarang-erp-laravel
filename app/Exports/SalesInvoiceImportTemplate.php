<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalesInvoiceImportTemplate implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return collect([
            [
                'CUST001',
                '2025-12-15',
                '2026-01-15',
                'PO-2025-001',
                'DO-2025-001',
                '4.1.1',
                'Product Sales',
                '10',
                '100000',
                'PPN11',
            ],
            [
                'CUST002',
                '2025-12-20',
                '2026-01-20',
                'PO-2025-002',
                'DO-2025-002',
                '4.1.1',
                'Service Sales (No VAT)',
                '5',
                '50000',
                '', // Empty for no VAT
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Document Date',
            'Due Date',
            'Reference No',
            'Delivery Order No',
            'Account Code',
            'Description',
            'Qty',
            'Unit Price',
            'Tax Code',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 20,
            'E' => 20,
            'F' => 15,
            'G' => 30,
            'H' => 10,
            'I' => 15,
            'J' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
