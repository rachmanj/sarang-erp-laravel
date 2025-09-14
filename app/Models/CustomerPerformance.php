<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPerformance extends Model
{
    protected $fillable = [
        'customer_id',
        'year',
        'month',
        'total_orders',
        'total_amount',
        'avg_order_value',
        'payment_performance',
        'profitability_rating',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'payment_performance' => 'decimal:2',
        'profitability_rating' => 'decimal:2',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
    }

    // Scopes
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeTopCustomers($query, $limit = 10)
    {
        return $query->orderBy('total_amount', 'desc')->limit($limit);
    }

    public function scopeHighProfitability($query, $minRating = 4.0)
    {
        return $query->where('profitability_rating', '>=', $minRating);
    }

    // Accessors
    public function getPerformanceGradeAttribute()
    {
        if ($this->profitability_rating >= 4.5) {
            return 'A+';
        } elseif ($this->profitability_rating >= 4.0) {
            return 'A';
        } elseif ($this->profitability_rating >= 3.5) {
            return 'B+';
        } elseif ($this->profitability_rating >= 3.0) {
            return 'B';
        } elseif ($this->profitability_rating >= 2.5) {
            return 'C+';
        } elseif ($this->profitability_rating >= 2.0) {
            return 'C';
        } else {
            return 'D';
        }
    }

    public function getPerformanceStatusAttribute()
    {
        if ($this->profitability_rating >= 4.0) {
            return 'excellent';
        } elseif ($this->profitability_rating >= 3.0) {
            return 'good';
        } elseif ($this->profitability_rating >= 2.0) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    public function getPaymentStatusAttribute()
    {
        if ($this->payment_performance >= 95) {
            return 'excellent';
        } elseif ($this->payment_performance >= 85) {
            return 'good';
        } elseif ($this->payment_performance >= 70) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    // Methods
    public function calculateProfitabilityRating($grossProfitMargin)
    {
        // Convert percentage to 0-5 scale
        return min(5.0, max(0.0, $grossProfitMargin / 20));
    }

    public function updatePerformanceMetrics($orderData = [])
    {
        if (isset($orderData['total_orders'])) {
            $this->total_orders += $orderData['total_orders'];
        }

        if (isset($orderData['total_amount'])) {
            $this->total_amount += $orderData['total_amount'];
        }

        if (isset($orderData['avg_order_value'])) {
            $this->avg_order_value = $orderData['avg_order_value'];
        }

        if (isset($orderData['payment_performance'])) {
            $this->payment_performance = $orderData['payment_performance'];
        }

        if (isset($orderData['profitability_rating'])) {
            $this->profitability_rating = $orderData['profitability_rating'];
        }

        $this->save();
    }

    public static function getTopCustomers($year = null, $limit = 10)
    {
        $query = self::with('customer');

        if ($year) {
            $query = $query->forYear($year);
        }

        return $query->topCustomers($limit)->get();
    }

    public static function getCustomerRanking($customerId, $year = null)
    {
        $query = self::where('customer_id', $customerId);

        if ($year) {
            $query = $query->forYear($year);
        }

        $customerPerformance = $query->first();

        if (!$customerPerformance) {
            return null;
        }

        $totalCustomers = self::when($year, function ($q) use ($year) {
            return $q->forYear($year);
        })->count();

        $betterCustomers = self::when($year, function ($q) use ($year) {
            return $q->forYear($year);
        })->where('total_amount', '>', $customerPerformance->total_amount)->count();

        $ranking = $betterCustomers + 1;
        $percentile = round((($totalCustomers - $ranking + 1) / $totalCustomers) * 100, 1);

        return [
            'ranking' => $ranking,
            'total_customers' => $totalCustomers,
            'percentile' => $percentile,
            'performance' => $customerPerformance,
        ];
    }

    public static function getMonthlyTrend($customerId, $year)
    {
        return self::where('customer_id', $customerId)
            ->where('year', $year)
            ->orderBy('month')
            ->get()
            ->map(function ($performance) {
                return [
                    'month' => $performance->month,
                    'total_amount' => $performance->total_amount,
                    'total_orders' => $performance->total_orders,
                    'profitability_rating' => $performance->profitability_rating,
                ];
            });
    }
}
