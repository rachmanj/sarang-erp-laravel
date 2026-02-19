<?php

namespace App\Exports;

use App\Models\InventoryItem;
use App\Models\InventoryValuation;
use Illuminate\Support\Collection;
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

class InventoryDetailReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected string $reportDate;

    public function __construct(string $reportDate)
    {
        $this->reportDate = $reportDate;
    }

    public function collection(): Collection
    {
        $items = InventoryItem::with('category')
            ->where('item_type', 'item')
            ->active()
            ->orderBy('code')
            ->get();

        $latestValuations = InventoryValuation::where('valuation_date', '<=', $this->reportDate)
            ->orderByDesc('valuation_date')
            ->get()
            ->unique('item_id')
            ->keyBy('item_id');

        return $items->map(function ($item) use ($latestValuations) {
            $valuation = $latestValuations->get($item->id);
            return (object) [
                'item' => $item,
                'valuation' => $valuation,
                'quantity_on_hand' => $valuation?->quantity_on_hand ?? 0,
                'unit_cost' => $valuation?->unit_cost ?? $item->purchase_price,
                'total_value' => $valuation?->total_value ?? 0,
                'valuation_method' => $valuation?->valuation_method ?? $item->valuation_method,
                'valuation_date' => $valuation?->valuation_date,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Item Name',
            'Category',
            'Unit',
            'Quantity on Hand',
            'Unit Cost',
            'Total Value',
            'Valuation Method',
            'Valuation Date',
            'Reorder Point',
            'Stock Status',
        ];
    }

    public function map($row): array
    {
        $item = $row->item;
        $qty = $row->quantity_on_hand;
        $status = 'OK';
        if ($qty <= 0) {
            $status = 'Out of Stock';
        } elseif ($item->reorder_point > 0 && $qty <= $item->reorder_point) {
            $status = 'Low Stock';
        }

        return [
            $item->code,
            $item->name,
            $item->category->name ?? 'N/A',
            $item->unit_of_measure,
            $qty,
            $row->unit_cost,
            $row->total_value,
            strtoupper($row->valuation_method),
            $row->valuation_date ? $row->valuation_date->format('d/m/Y') : '-',
            $item->reorder_point,
            $status,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 35,
            'C' => 20,
            'D' => 10,
            'E' => 15,
            'F' => 15,
            'G' => 18,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                if ($lastRow > 1) {
                    $totalsRow = $lastRow + 2;
                    $sheet->setCellValue('A' . $totalsRow, 'TOTAL:');
                    $sheet->setCellValue('E' . $totalsRow, '=SUM(E2:E' . $lastRow . ')');
                    $sheet->setCellValue('G' . $totalsRow, '=SUM(G2:G' . $lastRow . ')');

                    $sheet->getStyle('A' . $totalsRow . ':K' . $totalsRow)->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF3E0'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('F2:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('F' . $totalsRow . ':G' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
