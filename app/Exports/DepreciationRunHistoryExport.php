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

class DepreciationRunHistoryExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(AssetReportService::class)->getDepreciationRunHistory($this->filters);
    }

    public function headings(): array
    {
        return [
            'Period',
            'Status',
            'Asset Count',
            'Entry Count',
            'Total Depreciation',
            'Posted At',
            'Notes',
        ];
    }

    public function map($run): array
    {
        return [
            $run->period,
            ucfirst($run->status),
            $run->asset_count,
            $run->entry_count,
            $run->total_depreciation,
            $run->posted_at ? $run->posted_at->format('d/m/Y H:i') : '',
            $run->notes ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 12,
            'C' => 12,
            'D' => 12,
            'E' => 18,
            'F' => 18,
            'G' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8EAF6'],
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
