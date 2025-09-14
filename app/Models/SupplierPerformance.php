<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformance extends Model
{
    protected $fillable = [
        'vendor_id',
        'year',
        'month',
        'total_orders',
        'total_amount',
        'avg_delivery_days',
        'quality_rating',
        'price_rating',
        'service_rating',
        'overall_rating',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'avg_delivery_days' => 'decimal:2',
        'quality_rating' => 'decimal:2',
        'price_rating' => 'decimal:2',
        'service_rating' => 'decimal:2',
        'overall_rating' => 'decimal:2',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Vendor::class, 'vendor_id');
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

    public function scopeTopRated($query, $limit = 10)
    {
        return $query->orderBy('overall_rating', 'desc')->limit($limit);
    }

    // Methods
    public function calculateOverallRating()
    {
        $this->overall_rating = round(
            ($this->quality_rating + $this->price_rating + $this->service_rating) / 3,
            2
        );
        $this->save();
    }

    public function getPerformanceGrade()
    {
        if ($this->overall_rating >= 4.5) {
            return 'A+';
        } elseif ($this->overall_rating >= 4.0) {
            return 'A';
        } elseif ($this->overall_rating >= 3.5) {
            return 'B+';
        } elseif ($this->overall_rating >= 3.0) {
            return 'B';
        } elseif ($this->overall_rating >= 2.5) {
            return 'C+';
        } elseif ($this->overall_rating >= 2.0) {
            return 'C';
        } else {
            return 'D';
        }
    }

    public function getPerformanceStatus()
    {
        if ($this->overall_rating >= 4.0) {
            return 'excellent';
        } elseif ($this->overall_rating >= 3.0) {
            return 'good';
        } elseif ($this->overall_rating >= 2.0) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    public static function updatePerformanceMetrics($vendorId, $year, $month, $orderData = [])
    {
        $performance = self::firstOrNew([
            'vendor_id' => $vendorId,
            'year' => $year,
            'month' => $month,
        ]);

        // Update order metrics
        if (isset($orderData['total_orders'])) {
            $performance->total_orders += $orderData['total_orders'];
        }

        if (isset($orderData['total_amount'])) {
            $performance->total_amount += $orderData['total_amount'];
        }

        if (isset($orderData['avg_delivery_days'])) {
            $performance->avg_delivery_days = $orderData['avg_delivery_days'];
        }

        // Update ratings if provided
        if (isset($orderData['quality_rating'])) {
            $performance->quality_rating = $orderData['quality_rating'];
        }

        if (isset($orderData['price_rating'])) {
            $performance->price_rating = $orderData['price_rating'];
        }

        if (isset($orderData['service_rating'])) {
            $performance->service_rating = $orderData['service_rating'];
        }

        $performance->save();
        $performance->calculateOverallRating();

        return $performance;
    }
}
