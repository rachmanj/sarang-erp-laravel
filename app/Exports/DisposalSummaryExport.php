<?php

namespace App\Exports;

use App\Models\AssetDisposal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DisposalSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = AssetDisposal::with(['asset.category', 'creator', 'poster'])
            ->select([
                'asset_disposals.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name'
            ])
            ->join('assets', 'asset_disposals.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        // Apply filters
        if (isset($this->filters['disposal_type']) && $this->filters['disposal_type']) {
            $query->where('asset_disposals.disposal_type', $this->filters['disposal_type']);
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('asset_disposals.status', $this->filters['status']);
        }

        if (isset($this->filters['date_from']) && $this->filters['date_from']) {
            $query->where('asset_disposals.disposal_date', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to']) && $this->filters['date_to']) {
            $query->where('asset_disposals.disposal_date', '<=', $this->filters['date_to']);
        }

        if (isset($this->filters['category_id']) && $this->filters['category_id']) {
            $query->where('assets.category_id', $this->filters['category_id']);
        }

        return $query->orderBy('asset_disposals.disposal_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Disposal Date',
            'Disposal Type',
            'Disposal Proceeds',
            'Book Value at Disposal',
            'Gain/Loss Amount',
            'Gain/Loss Type',
            'Status',
            'Disposal Reason',
            'Disposal Method',
            'Reference Number',
            'Created By',
            'Posted By',
            'Posted Date',
            'Notes'
        ];
    }

    public function map($disposal): array
    {
        return [
            $disposal->asset_code,
            $disposal->asset_name,
            $disposal->category_name,
            $disposal->disposal_date ? $disposal->disposal_date->format('d/m/Y') : '',
            ucfirst(str_replace('_', ' ', $disposal->disposal_type)),
            $disposal->disposal_proceeds ?? 0,
            $disposal->book_value_at_disposal,
            $disposal->gain_loss_amount,
            ucfirst($disposal->gain_loss_type),
            ucfirst($disposal->status),
            $disposal->disposal_reason ?? '',
            $disposal->disposal_method ?? '',
            $disposal->disposal_reference ?? '',
            $disposal->creator ? $disposal->creator->name : '',
            $disposal->poster ? $disposal->poster->name : '',
            $disposal->posted_at ? $disposal->posted_at->format('d/m/Y H:i') : '',
            $disposal->notes ?? ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Asset Code
            'B' => 30, // Asset Name
            'C' => 20, // Category
            'D' => 15, // Disposal Date
            'E' => 15, // Disposal Type
            'F' => 18, // Disposal Proceeds
            'G' => 20, // Book Value at Disposal
            'H' => 18, // Gain/Loss Amount
            'I' => 15, // Gain/Loss Type
            'J' => 12, // Status
            'K' => 25, // Disposal Reason
            'L' => 20, // Disposal Method
            'M' => 20, // Reference Number
            'N' => 20, // Created By
            'O' => 20, // Posted By
            'P' => 18, // Posted Date
            'Q' => 30, // Notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFEBEE'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add totals row
                $lastRow = $sheet->getHighestRow();
                $totalsRow = $lastRow + 2;

                $sheet->setCellValue('A' . $totalsRow, 'TOTAL:');
                $sheet->setCellValue('F' . $totalsRow, '=SUM(F2:F' . $lastRow . ')');
                $sheet->setCellValue('G' . $totalsRow, '=SUM(G2:G' . $lastRow . ')');
                $sheet->setCellValue('H' . $totalsRow, '=SUM(H2:H' . $lastRow . ')');

                // Style totals row
                $sheet->getStyle('A' . $totalsRow . ':Q' . $totalsRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3E0'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Format currency columns
                $sheet->getStyle('F2:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('F' . $totalsRow . ':H' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0');

                // Auto-fit columns
                foreach (range('A', 'Q') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                }
            },
        ];
    }
}
