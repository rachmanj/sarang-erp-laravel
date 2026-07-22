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

class AssetAgingExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(AssetReportService::class)->getAssetAging($this->filters);
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Placed in Service',
            'Years Owned',
            'Days Owned',
            'Acquisition Cost',
            'Book Value',
            'Life Months',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->code,
            $asset->name,
            $asset->category_name,
            $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '',
            $asset->years_owned,
            $asset->days_owned,
            $asset->acquisition_cost,
            $asset->current_book_value,
            $asset->life_months,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 18,
            'E' => 12,
            'F' => 12,
            'G' => 18,
            'H' => 15,
            'I' => 12,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3E5F5'],
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
