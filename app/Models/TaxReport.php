<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxReport extends Model
{
    protected $fillable = [
        'tax_period_id',
        'report_type',
        'report_name',
        'status',
        'submission_date',
        'due_date',
        'reference_number',
        'report_data',
        'notes',
        'created_by',
        'submitted_by'
    ];

    protected $casts = [
        'submission_date' => 'date',
        'due_date' => 'date',
        'report_data' => 'array',
    ];

    // Relationships
    public function taxPeriod(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class, 'tax_period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'submitted')
            ->where('status', '!=', 'approved');
    }

    public function scopeByType($query, $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->due_date &&
            $this->due_date < now()->toDateString() &&
            !in_array($this->status, ['submitted', 'approved']);
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'draft':
                return 'secondary';
            case 'submitted':
                return 'info';
            case 'approved':
                return 'success';
            case 'rejected':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    public function getReportTypeNameAttribute()
    {
        $reportTypes = [
            'spt_ppn' => 'SPT PPN',
            'spt_pph_21' => 'SPT PPh 21',
            'spt_pph_22' => 'SPT PPh 22',
            'spt_pph_23' => 'SPT PPh 23',
            'spt_pph_26' => 'SPT PPh 26',
            'spt_pph_4_2' => 'SPT PPh 4(2)',
            'spt_tahunan' => 'SPT Tahunan',
        ];

        return $reportTypes[$this->report_type] ?? $this->report_type;
    }

    // Methods
    public function canBeSubmitted()
    {
        return $this->status === 'draft';
    }

    public function canBeApproved()
    {
        return $this->status === 'submitted';
    }

    public function canBeRejected()
    {
        return $this->status === 'submitted';
    }

    public function submit($userId = null)
    {
        if (!$this->canBeSubmitted()) {
            throw new \Exception('Report cannot be submitted in current status');
        }

        $this->update([
            'status' => 'submitted',
            'submission_date' => now()->toDateString(),
            'submitted_by' => $userId,
        ]);
    }

    public function approve()
    {
        if (!$this->canBeApproved()) {
            throw new \Exception('Report cannot be approved in current status');
        }

        $this->update(['status' => 'approved']);
    }

    public function reject($notes = null)
    {
        if (!$this->canBeRejected()) {
            throw new \Exception('Report cannot be rejected in current status');
        }

        $this->update([
            'status' => 'rejected',
            'notes' => $notes ? ($this->notes . "\nRejected: " . $notes) : $this->notes,
        ]);
    }

    public function generateReportData()
    {
        $period = $this->taxPeriod;

        switch ($this->report_type) {
            case 'spt_ppn':
                return $this->generatePPNReportData($period);
            case 'spt_pph_21':
                return $this->generatePPh21ReportData($period);
            case 'spt_pph_22':
                return $this->generatePPh22ReportData($period);
            case 'spt_pph_23':
                return $this->generatePPh23ReportData($period);
            case 'spt_pph_26':
                return $this->generatePPh26ReportData($period);
            case 'spt_pph_4_2':
                return $this->generatePPh42ReportData($period);
            default:
                return [];
        }
    }

    private function generatePPNReportData($period)
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [
            $period->start_date,
            $period->end_date
        ])->where('tax_type', 'ppn')->get();

        return [
            'period' => $period->period_name,
            'ppn_input' => $transactions->where('tax_category', 'input')->sum('tax_amount'),
            'ppn_output' => $transactions->where('tax_category', 'output')->sum('tax_amount'),
            'ppn_net' => $transactions->where('tax_category', 'output')->sum('tax_amount') -
                $transactions->where('tax_category', 'input')->sum('tax_amount'),
            'transaction_count' => $transactions->count(),
            'transactions' => $transactions->toArray(),
        ];
    }

    private function generatePPh21ReportData($period)
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [
            $period->start_date,
            $period->end_date
        ])->where('tax_type', 'pph_21')->get();

        return [
            'period' => $period->period_name,
            'total_withholding' => $transactions->sum('tax_amount'),
            'transaction_count' => $transactions->count(),
            'transactions' => $transactions->toArray(),
        ];
    }

    private function generatePPh22ReportData($period)
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [
            $period->start_date,
            $period->end_date
        ])->where('tax_type', 'pph_22')->get();

        return [
            'period' => $period->period_name,
            'total_withholding' => $transactions->sum('tax_amount'),
            'transaction_count' => $transactions->count(),
            'transactions' => $transactions->toArray(),
        ];
    }

    private function generatePPh23ReportData($period)
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [
            $period->start_date,
            $period->end_date
        ])->where('tax_type', 'pph_23')->get();

        return [
            'period' => $period->period_name,
            'total_withholding' => $transactions->sum('tax_amount'),
            'transaction_count' => $transactions->count(),
            'transactions' => $transactions->toArray(),
        ];
    }

    private function generatePPh26ReportData($period)
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [
            $period->start_date,
            $period->end_date
        ])->where('tax_type', 'pph_26')->get();

        return [
            'period' => $period->period_name,
            'total_withholding' => $transactions->sum('tax_amount'),
            'transaction_count' => $transactions->count(),
            'transactions' => $transactions->toArray(),
        ];
    }

    private function generatePPh42ReportData($period)
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [
            $period->start_date,
            $period->end_date
        ])->where('tax_type', 'pph_4_2')->get();

        return [
            'period' => $period->period_name,
            'total_withholding' => $transactions->sum('tax_amount'),
            'transaction_count' => $transactions->count(),
            'transactions' => $transactions->toArray(),
        ];
    }

    public static function getOverdueReports()
    {
        return self::overdue()->with('taxPeriod')->get();
    }

    public static function getReportsByPeriod($periodId)
    {
        return self::where('tax_period_id', $periodId)->get();
    }
}
