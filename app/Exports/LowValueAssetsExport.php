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

class LowValueAssetsExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected float $threshold;

    public function __construct(float $threshold = 1000000)
    {
        $this->threshold = $threshold;
    }

    public function collection()
    {
        return app(AssetReportService::class)->getLowValueAssets($this->threshold);
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Placed in Service',
            'Acquisition Cost',
            'Book Value',
            'Status',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->code,
            $asset->name,
            $asset->category?->name ?? '',
            $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '',
            $asset->acquisition_cost,
            $asset->current_book_value,
            ucfirst($asset->status),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 18,
            'E' => 18,
            'F' => 15,
            'G' => 12,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF8E1'],
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
