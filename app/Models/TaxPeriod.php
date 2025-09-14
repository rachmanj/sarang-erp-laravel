<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxPeriod extends Model
{
    protected $fillable = [
        'year',
        'month',
        'period_type',
        'start_date',
        'end_date',
        'status',
        'closing_date',
        'closed_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closing_date' => 'date',
    ];

    // Relationships
    public function taxReports(): HasMany
    {
        return $this->hasMany(TaxReport::class, 'tax_period_id');
    }

    public function taxTransactions(): HasMany
    {
        return $this->hasMany(TaxTransaction::class, 'tax_period_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeCurrent($query)
    {
        return $query->where('year', now()->year)
            ->where('month', now()->month);
    }

    // Accessors
    public function getPeriodNameAttribute()
    {
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $monthNames[$this->month] . ' ' . $this->year;
    }

    public function getIsCurrentAttribute()
    {
        return $this->year == now()->year && $this->month == now()->month;
    }

    public function getIsPastAttribute()
    {
        return $this->end_date < now()->toDateString();
    }

    public function getIsFutureAttribute()
    {
        return $this->start_date > now()->toDateString();
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->status !== 'open') {
            return 0;
        }

        $daysRemaining = now()->diffInDays($this->end_date, false);
        return max(0, $daysRemaining);
    }

    // Methods
    public function canBeClosed()
    {
        return $this->status === 'open' && $this->is_past;
    }

    public function close($userId = null)
    {
        if (!$this->canBeClosed()) {
            throw new \Exception('Tax period cannot be closed in current status');
        }

        $this->update([
            'status' => 'closed',
            'closing_date' => now()->toDateString(),
            'closed_by' => $userId,
        ]);
    }

    public function lock()
    {
        $this->update(['status' => 'locked']);
    }

    public function unlock()
    {
        if ($this->status === 'locked') {
            $this->update(['status' => 'closed']);
        }
    }

    public function getTaxSummary()
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [
            $this->start_date,
            $this->end_date
        ])->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_taxable_amount' => $transactions->sum('taxable_amount'),
            'total_tax_amount' => $transactions->sum('tax_amount'),
            'total_amount' => $transactions->sum('total_amount'),
            'ppn_input' => $transactions->where('tax_type', 'ppn')
                ->where('tax_category', 'input')
                ->sum('tax_amount'),
            'ppn_output' => $transactions->where('tax_type', 'ppn')
                ->where('tax_category', 'output')
                ->sum('tax_amount'),
            'pph_21' => $transactions->where('tax_type', 'pph_21')->sum('tax_amount'),
            'pph_22' => $transactions->where('tax_type', 'pph_22')->sum('tax_amount'),
            'pph_23' => $transactions->where('tax_type', 'pph_23')->sum('tax_amount'),
            'pph_26' => $transactions->where('tax_type', 'pph_26')->sum('tax_amount'),
            'pph_4_2' => $transactions->where('tax_type', 'pph_4_2')->sum('tax_amount'),
        ];
    }

    public static function getCurrentPeriod()
    {
        return self::current()->first();
    }

    public static function createPeriod($year, $month, $periodType = 'monthly')
    {
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth()->toDateString();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth()->toDateString();

        return self::create([
            'year' => $year,
            'month' => $month,
            'period_type' => $periodType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
        ]);
    }

    public static function getUpcomingPeriods($limit = 3)
    {
        return self::where('start_date', '>', now()->toDateString())
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }

    public static function getOverduePeriods()
    {
        return self::where('end_date', '<', now()->toDateString())
            ->where('status', 'open')
            ->orderBy('end_date')
            ->get();
    }
}
