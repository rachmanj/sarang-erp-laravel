<?php

namespace App\Exports;

use App\Models\Asset;
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

class AssetRegisterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Asset::with(['category', 'fund', 'project', 'department', 'vendor'])
            ->select([
                'assets.*',
                'asset_categories.name as category_name',
                'funds.name as fund_name',
                'projects.name as project_name',
                'departments.name as department_name',
                'vendors.name as vendor_name'
            ])
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->leftJoin('funds', 'assets.fund_id', '=', 'funds.id')
            ->leftJoin('projects', 'assets.project_id', '=', 'projects.id')
            ->leftJoin('departments', 'assets.department_id', '=', 'departments.id')
            ->leftJoin('vendors', 'assets.vendor_id', '=', 'vendors.id');

        // Apply filters
        if (isset($this->filters['category_id']) && $this->filters['category_id']) {
            $query->where('assets.category_id', $this->filters['category_id']);
        }

        if (isset($this->filters['fund_id']) && $this->filters['fund_id']) {
            $query->where('assets.fund_id', $this->filters['fund_id']);
        }

        if (isset($this->filters['project_id']) && $this->filters['project_id']) {
            $query->where('assets.project_id', $this->filters['project_id']);
        }

        if (isset($this->filters['department_id']) && $this->filters['department_id']) {
            $query->where('assets.department_id', $this->filters['department_id']);
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('assets.status', $this->filters['status']);
        }

        if (isset($this->filters['date_from']) && $this->filters['date_from']) {
            $query->where('assets.acquisition_date', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to']) && $this->filters['date_to']) {
            $query->where('assets.acquisition_date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('assets.code')->get();
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Fund',
            'Project',
            'Department',
            'Vendor',
            'Acquisition Date',
            'Acquisition Cost',
            'Accumulated Depreciation',
            'Book Value',
            'Status',
            'Depreciable',
            'Useful Life (Months)',
            'Depreciation Method',
            'Placed in Service Date',
            'Disposal Date',
            'Notes'
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->code,
            $asset->name,
            $asset->category_name,
            $asset->fund_name ?? '',
            $asset->project_name ?? '',
            $asset->department_name ?? '',
            $asset->vendor_name ?? '',
            $asset->acquisition_date ? $asset->acquisition_date->format('d/m/Y') : '',
            $asset->acquisition_cost,
            $asset->accumulated_depreciation,
            $asset->current_book_value,
            ucfirst($asset->status),
            $asset->is_depreciable ? 'Yes' : 'No',
            $asset->useful_life_months,
            $asset->depreciation_method ?? 'Straight Line',
            $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '',
            $asset->disposal_date ? $asset->disposal_date->format('d/m/Y') : '',
            $asset->notes ?? ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Asset Code
            'B' => 30, // Asset Name
            'C' => 20, // Category
            'D' => 20, // Fund
            'E' => 20, // Project
            'F' => 20, // Department
            'G' => 25, // Vendor
            'H' => 15, // Acquisition Date
            'I' => 18, // Acquisition Cost
            'J' => 22, // Accumulated Depreciation
            'K' => 15, // Book Value
            'L' => 12, // Status
            'M' => 12, // Depreciable
            'N' => 18, // Useful Life
            'O' => 20, // Depreciation Method
            'P' => 20, // Placed in Service Date
            'Q' => 15, // Disposal Date
            'R' => 30, // Notes
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
                    'startColor' => ['rgb' => 'E3F2FD'],
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
                $sheet->setCellValue('I' . $totalsRow, '=SUM(I2:I' . $lastRow . ')');
                $sheet->setCellValue('J' . $totalsRow, '=SUM(J2:J' . $lastRow . ')');
                $sheet->setCellValue('K' . $totalsRow, '=SUM(K2:K' . $lastRow . ')');

                // Style totals row
                $sheet->getStyle('A' . $totalsRow . ':R' . $totalsRow)->applyFromArray([
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
                $sheet->getStyle('I2:K' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('I' . $totalsRow . ':K' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0');

                // Auto-fit columns
                foreach (range('A', 'R') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                }
            },
        ];
    }
}
