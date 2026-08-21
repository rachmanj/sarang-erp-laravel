<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountLedgerExport implements FromArray, WithStyles, WithColumnWidths
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

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 16,
            'C' => 40,
            'D' => 22,
            'E' => 16,
            'F' => 16,
            'G' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header column row (row 1)
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
        ];
    }
}
