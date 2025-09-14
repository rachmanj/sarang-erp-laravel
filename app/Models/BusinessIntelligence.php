<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessIntelligence extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'report_name',
        'report_date',
        'period_start',
        'period_end',
        'data_json',
        'insights_json',
        'recommendations_json',
        'kpi_metrics_json',
        'trend_analysis_json',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'data_json' => 'array',
        'insights_json' => 'array',
        'recommendations_json' => 'array',
        'kpi_metrics_json' => 'array',
        'trend_analysis_json' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    public function getInsightsAttribute()
    {
        return $this->insights_json ?? [];
    }

    public function getRecommendationsAttribute()
    {
        return $this->recommendations_json ?? [];
    }

    public function getKpiMetricsAttribute()
    {
        return $this->kpi_metrics_json ?? [];
    }

    public function getTrendAnalysisAttribute()
    {
        return $this->trend_analysis_json ?? [];
    }

    public function getDataAttribute()
    {
        return $this->data_json ?? [];
    }
}
