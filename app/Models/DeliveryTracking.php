<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTracking extends Model
{
    protected $fillable = [
        'delivery_order_id',
        'pickup_time',
        'departure_time',
        'estimated_arrival_time',
        'actual_arrival_time',
        'delivery_completion_time',
        'delivery_duration_minutes',
        'distance_km',
        'fuel_cost',
        'driver_cost',
        'vehicle_cost',
        'total_logistics_cost',
        'customer_satisfaction_score',
        'delivery_notes',
        'weather_conditions',
        'traffic_conditions',
        'delivery_attempts',
        'return_reason',
        'rescheduled_reason',
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'departure_time' => 'datetime',
        'estimated_arrival_time' => 'datetime',
        'actual_arrival_time' => 'datetime',
        'delivery_completion_time' => 'datetime',
        'delivery_duration_minutes' => 'integer',
        'distance_km' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'driver_cost' => 'decimal:2',
        'vehicle_cost' => 'decimal:2',
        'total_logistics_cost' => 'decimal:2',
        'customer_satisfaction_score' => 'integer',
        'delivery_attempts' => 'integer',
    ];

    // Relationships
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    // Accessors
    public function getDeliveryEfficiencyAttribute()
    {
        if ($this->estimated_arrival_time && $this->actual_arrival_time) {
            $estimated = $this->estimated_arrival_time;
            $actual = $this->actual_arrival_time;

            if ($actual <= $estimated) {
                return 'on_time';
            } elseif ($actual->diffInMinutes($estimated) <= 30) {
                return 'slightly_delayed';
            } else {
                return 'significantly_delayed';
            }
        }

        return 'unknown';
    }

    public function getTotalDeliveryTimeAttribute()
    {
        if ($this->pickup_time && $this->delivery_completion_time) {
            return $this->pickup_time->diffInMinutes($this->delivery_completion_time);
        }

        return null;
    }

    public function getCostPerKmAttribute()
    {
        if ($this->total_logistics_cost && $this->distance_km > 0) {
            return $this->total_logistics_cost / $this->distance_km;
        }

        return 0;
    }

    // Methods
    public function calculateLogisticsCost()
    {
        $this->total_logistics_cost = $this->fuel_cost + $this->driver_cost + $this->vehicle_cost;
        $this->save();
    }

    public function recordDeliveryAttempt($successful = true, $notes = null)
    {
        $this->delivery_attempts++;

        if ($successful) {
            $this->delivery_completion_time = now();
            $this->delivery_notes = $notes;
        }

        $this->save();
    }

    public function recordCustomerSatisfaction($score, $notes = null)
    {
        $this->customer_satisfaction_score = $score;
        $this->delivery_notes = $notes;
        $this->save();
    }
}
