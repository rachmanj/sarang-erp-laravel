<?php

namespace App\Exports;

use App\Services\Reports\AssetReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepreciationScheduleExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(AssetReportService::class)->getDepreciationSchedule($this->filters);
    }

    public function headings(): array
    {
        return [
            'Period',
            'Asset Code',
            'Asset Name',
            'Category',
            'Depreciation Amount',
            'Book',
            'Status',
        ];
    }

    public function map($entry): array
    {
        return [
            $entry->period,
            $entry->asset_code,
            $entry->asset_name,
            $entry->category_name,
            $entry->amount,
            ucfirst($entry->book ?? 'financial'),
            ucfirst($entry->entry_status ?? 'draft'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 15,
            'C' => 30,
            'D' => 20,
            'E' => 18,
            'F' => 12,
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
                    'startColor' => ['rgb' => 'E8F5E8'],
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $totalsRow = $lastRow + 2;

                $sheet->setCellValue('A'.$totalsRow, 'TOTAL:');
                $sheet->setCellValue('E'.$totalsRow, '=SUM(E2:E'.$lastRow.')');

                $sheet->getStyle('A'.$totalsRow.':G'.$totalsRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3E0'],
                    ],
                ]);

                $sheet->getStyle('E2:E'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('E'.$totalsRow)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
