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

class AssetRegisterExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(AssetReportService::class)->getAssetRegister($this->filters);
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Project',
            'Department',
            'Vendor',
            'Placed in Service Date',
            'Acquisition Cost',
            'Accumulated Depreciation',
            'Book Value',
            'Status',
            'Useful Life (Months)',
            'Depreciation Method',
            'Disposal Date',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->code,
            $asset->name,
            $asset->category_name,
            $asset->project_name ?? '',
            $asset->department_name ?? '',
            $asset->vendor_name ?? '',
            $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '',
            $asset->acquisition_cost,
            $asset->accumulated_depreciation,
            $asset->current_book_value,
            ucfirst($asset->status),
            $asset->life_months,
            $asset->method ?? 'straight_line',
            $asset->disposal_date ? $asset->disposal_date->format('d/m/Y') : '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 25,
            'G' => 20,
            'H' => 18,
            'I' => 22,
            'J' => 15,
            'K' => 12,
            'L' => 18,
            'M' => 20,
            'N' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
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
                $lastRow = $sheet->getHighestRow();
                $totalsRow = $lastRow + 2;

                $sheet->setCellValue('A'.$totalsRow, 'TOTAL:');
                $sheet->setCellValue('H'.$totalsRow, '=SUM(H2:H'.$lastRow.')');
                $sheet->setCellValue('I'.$totalsRow, '=SUM(I2:I'.$lastRow.')');
                $sheet->setCellValue('J'.$totalsRow, '=SUM(J2:J'.$lastRow.')');

                $sheet->getStyle('A'.$totalsRow.':N'.$totalsRow)->applyFromArray([
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

                $sheet->getStyle('H2:J'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('H'.$totalsRow.':J'.$totalsRow)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
