<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AuditLogExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $logs;

    public function __construct(Collection $logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'Timestamp',
            'User',
            'Action',
            'Entity Type',
            'Entity ID',
            'Description',
            'IP Address',
            'User Agent',
            'Old Values',
            'New Values',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user ? $log->user->name : 'System',
            ucfirst($log->action),
            ucwords(str_replace('_', ' ', $log->entity_type)),
            $log->entity_id,
            $log->description ?? '',
            $log->ip_address ?? '',
            $log->user_agent ?? '',
            $log->old_values ? json_encode($log->old_values) : '',
            $log->new_values ? json_encode($log->new_values) : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 15,
            'D' => 20,
            'E' => 15,
            'F' => 50,
            'G' => 18,
            'H' => 50,
            'I' => 30,
            'J' => 30,
        ];
    }
}

