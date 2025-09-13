<?php

namespace App\Exports;

use App\Models\AssetDepreciationEntry;
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

class DepreciationScheduleExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = AssetDepreciationEntry::with(['asset.category', 'journal'])
            ->select([
                'asset_depreciation_entries.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name',
                'asset_depreciation_runs.period_start',
                'asset_depreciation_runs.period_end'
            ])
            ->join('assets', 'asset_depreciation_entries.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->join('asset_depreciation_runs', 'asset_depreciation_entries.run_id', '=', 'asset_depreciation_runs.id');

        // Apply filters
        if (isset($this->filters['asset_id']) && $this->filters['asset_id']) {
            $query->where('asset_depreciation_entries.asset_id', $this->filters['asset_id']);
        }

        if (isset($this->filters['category_id']) && $this->filters['category_id']) {
            $query->where('assets.category_id', $this->filters['category_id']);
        }

        if (isset($this->filters['period_from']) && $this->filters['period_from']) {
            $query->where('asset_depreciation_runs.period_start', '>=', $this->filters['period_from']);
        }

        if (isset($this->filters['period_to']) && $this->filters['period_to']) {
            $query->where('asset_depreciation_runs.period_end', '<=', $this->filters['period_to']);
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('asset_depreciation_entries.status', $this->filters['status']);
        }

        return $query->orderBy('asset_depreciation_runs.period_start', 'desc')
            ->orderBy('assets.code')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Period Start',
            'Period End',
            'Depreciation Amount',
            'Status',
            'Journal Number',
            'Posted Date',
            'Fund',
            'Project',
            'Department',
            'Memo'
        ];
    }

    public function map($entry): array
    {
        return [
            $entry->asset_code,
            $entry->asset_name,
            $entry->category_name,
            $entry->period_start ? \Carbon\Carbon::parse($entry->period_start)->format('d/m/Y') : '',
            $entry->period_end ? \Carbon\Carbon::parse($entry->period_end)->format('d/m/Y') : '',
            $entry->amount,
            ucfirst($entry->status),
            $entry->journal ? $entry->journal->journal_no : '',
            $entry->journal ? $entry->journal->created_at->format('d/m/Y H:i') : '',
            $entry->fund ? $entry->fund->name : '',
            $entry->project ? $entry->project->name : '',
            $entry->department ? $entry->department->name : '',
            $entry->memo ?? ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Asset Code
            'B' => 30, // Asset Name
            'C' => 20, // Category
            'D' => 15, // Period Start
            'E' => 15, // Period End
            'F' => 18, // Depreciation Amount
            'G' => 12, // Status
            'H' => 20, // Journal Number
            'I' => 18, // Posted Date
            'J' => 20, // Fund
            'K' => 20, // Project
            'L' => 20, // Department
            'M' => 30, // Memo
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
                    'startColor' => ['rgb' => 'E8F5E8'],
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

                // Style totals row
                $sheet->getStyle('A' . $totalsRow . ':M' . $totalsRow)->applyFromArray([
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

                // Format currency column
                $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('F' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0');

                // Auto-fit columns
                foreach (range('A', 'M') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                }
            },
        ];
    }
}
