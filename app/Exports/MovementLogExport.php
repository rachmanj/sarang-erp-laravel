<?php

namespace App\Exports;

use App\Services\Reports\AssetReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovementLogExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(AssetReportService::class)->getMovementLog($this->filters);
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
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->asset_code,
            $movement->asset_name,
            $movement->category_name,
            $movement->movement_date ? $movement->movement_date->format('d/m/Y') : '',
            ucfirst(str_replace('_', ' ', $movement->movement_type ?? '')),
            $movement->from_location ?? '',
            $movement->to_location ?? '',
            $movement->from_custodian ?? '',
            $movement->to_custodian ?? '',
            ucfirst($movement->status),
            $movement->movement_reason ?? '',
            $movement->reference_number ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 15,
            'E' => 18,
            'F' => 20,
            'G' => 20,
            'H' => 18,
            'I' => 18,
            'J' => 12,
            'K' => 30,
            'L' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
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
