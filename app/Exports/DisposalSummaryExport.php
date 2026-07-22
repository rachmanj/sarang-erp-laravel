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

class DisposalSummaryExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(AssetReportService::class)->getDisposalSummary($this->filters);
    }

    public function headings(): array
    {
        return [
            'Disposal No',
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
        ];
    }

    public function map($disposal): array
    {
        return [
            $disposal->disposal_no,
            $disposal->asset_code,
            $disposal->asset_name,
            $disposal->category_name,
            $disposal->disposal_date ? $disposal->disposal_date->format('d/m/Y') : '',
            ucfirst(str_replace('_', ' ', $disposal->disposal_type ?? '')),
            $disposal->disposal_proceeds,
            $disposal->book_value_at_disposal,
            $disposal->gain_loss_amount,
            ucfirst($disposal->gain_loss_type ?? ''),
            ucfirst($disposal->status),
            $disposal->disposal_reason ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 30,
            'D' => 20,
            'E' => 15,
            'F' => 15,
            'G' => 18,
            'H' => 20,
            'I' => 18,
            'J' => 12,
            'K' => 12,
            'L' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFEBEE'],
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
                $sheet->setCellValue('G'.$totalsRow, '=SUM(G2:G'.$lastRow.')');
                $sheet->setCellValue('H'.$totalsRow, '=SUM(H2:H'.$lastRow.')');
                $sheet->setCellValue('I'.$totalsRow, '=SUM(I2:I'.$lastRow.')');

                $sheet->getStyle('A'.$totalsRow.':L'.$totalsRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3E0'],
                    ],
                ]);

                $sheet->getStyle('G2:I'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('G'.$totalsRow.':I'.$totalsRow)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
