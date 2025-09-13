<?php

namespace App\Exports;

use App\Models\AssetMovement;
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

class MovementLogExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = AssetMovement::with(['asset.category', 'creator', 'approver'])
            ->select([
                'asset_movements.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name'
            ])
            ->join('assets', 'asset_movements.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        // Apply filters
        if (isset($this->filters['movement_type']) && $this->filters['movement_type']) {
            $query->where('asset_movements.movement_type', $this->filters['movement_type']);
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('asset_movements.status', $this->filters['status']);
        }

        if (isset($this->filters['date_from']) && $this->filters['date_from']) {
            $query->where('asset_movements.movement_date', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to']) && $this->filters['date_to']) {
            $query->where('asset_movements.movement_date', '<=', $this->filters['date_to']);
        }

        if (isset($this->filters['asset_id']) && $this->filters['asset_id']) {
            $query->where('asset_movements.asset_id', $this->filters['asset_id']);
        }

        return $query->orderBy('asset_movements.movement_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Movement Date',
            'Movement Type',
            'From Location',
            'To Location',
            'From Custodian',
            'To Custodian',
            'Status',
            'Movement Reason',
            'Reference Number',
            'Created By',
            'Approved By',
            'Approved Date',
            'Notes'
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->asset_code,
            $movement->asset_name,
            $movement->category_name,
            $movement->movement_date ? $movement->movement_date->format('d/m/Y') : '',
            ucfirst(str_replace('_', ' ', $movement->movement_type)),
            $movement->from_location ?? '',
            $movement->to_location ?? '',
            $movement->from_custodian ?? '',
            $movement->to_custodian ?? '',
            ucfirst($movement->status),
            $movement->movement_reason ?? '',
            $movement->reference_number ?? '',
            $movement->creator ? $movement->creator->name : '',
            $movement->approver ? $movement->approver->name : '',
            $movement->approved_at ? $movement->approved_at->format('d/m/Y H:i') : '',
            $movement->notes ?? ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Asset Code
            'B' => 30, // Asset Name
            'C' => 20, // Category
            'D' => 15, // Movement Date
            'E' => 18, // Movement Type
            'F' => 20, // From Location
            'G' => 20, // To Location
            'H' => 20, // From Custodian
            'I' => 20, // To Custodian
            'J' => 12, // Status
            'K' => 25, // Movement Reason
            'L' => 20, // Reference Number
            'M' => 20, // Created By
            'N' => 20, // Approved By
            'O' => 18, // Approved Date
            'P' => 30, // Notes
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

                // Add summary row
                $lastRow = $sheet->getHighestRow();
                $summaryRow = $lastRow + 2;

                $sheet->setCellValue('A' . $summaryRow, 'SUMMARY:');
                $sheet->setCellValue('B' . $summaryRow, 'Total Movements: ' . ($lastRow - 1));

                // Style summary row
                $sheet->getStyle('A' . $summaryRow . ':P' . $summaryRow)->applyFromArray([
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

                // Auto-fit columns
                foreach (range('A', 'P') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                }
            },
        ];
    }
}
