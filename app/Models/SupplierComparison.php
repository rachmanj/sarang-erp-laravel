<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'comparison_date',
        'product_category_id',
        'supplier_1_id',
        'supplier_2_id',
        'supplier_3_id',
        'supplier_1_price',
        'supplier_2_price',
        'supplier_3_price',
        'supplier_1_delivery_days',
        'supplier_2_delivery_days',
        'supplier_3_delivery_days',
        'supplier_1_quality_score',
        'supplier_2_quality_score',
        'supplier_3_quality_score',
        'recommended_supplier_id',
        'cost_savings_potential',
        'notes',
    ];

    protected $casts = [
        'comparison_date' => 'date',
        'supplier_1_price' => 'decimal:4',
        'supplier_2_price' => 'decimal:4',
        'supplier_3_price' => 'decimal:4',
        'supplier_1_quality_score' => 'decimal:2',
        'supplier_2_quality_score' => 'decimal:2',
        'supplier_3_quality_score' => 'decimal:2',
        'cost_savings_potential' => 'decimal:4',
    ];

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function supplier1(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'supplier_1_id');
    }

    public function supplier2(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'supplier_2_id');
    }

    public function supplier3(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'supplier_3_id');
    }

    public function recommendedSupplier(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'recommended_supplier_id');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('product_category_id', $categoryId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('comparison_date', [$startDate, $endDate]);
    }

    public function getBestPriceSupplierAttribute()
    {
        $prices = [
            'supplier_1' => ['id' => $this->supplier_1_id, 'price' => $this->supplier_1_price],
            'supplier_2' => ['id' => $this->supplier_2_id, 'price' => $this->supplier_2_price],
            'supplier_3' => ['id' => $this->supplier_3_id, 'price' => $this->supplier_3_price],
        ];

        $prices = array_filter($prices, function ($supplier) {
            return $supplier['price'] > 0;
        });

        if (empty($prices)) {
            return null;
        }

        $bestPrice = min(array_column($prices, 'price'));
        $bestSupplier = array_search($bestPrice, array_column($prices, 'price'));

        return $prices[$bestSupplier];
    }

    public function getBestQualitySupplierAttribute()
    {
        $qualities = [
            'supplier_1' => ['id' => $this->supplier_1_id, 'quality' => $this->supplier_1_quality_score],
            'supplier_2' => ['id' => $this->supplier_2_id, 'quality' => $this->supplier_2_quality_score],
            'supplier_3' => ['id' => $this->supplier_3_id, 'quality' => $this->supplier_3_quality_score],
        ];

        $qualities = array_filter($qualities, function ($supplier) {
            return $supplier['quality'] > 0;
        });

        if (empty($qualities)) {
            return null;
        }

        $bestQuality = max(array_column($qualities, 'quality'));
        $bestSupplier = array_search($bestQuality, array_column($qualities, 'quality'));

        return $qualities[$bestSupplier];
    }

    public function getBestDeliverySupplierAttribute()
    {
        $deliveries = [
            'supplier_1' => ['id' => $this->supplier_1_id, 'days' => $this->supplier_1_delivery_days],
            'supplier_2' => ['id' => $this->supplier_2_id, 'days' => $this->supplier_2_delivery_days],
            'supplier_3' => ['id' => $this->supplier_3_id, 'days' => $this->supplier_3_delivery_days],
        ];

        $deliveries = array_filter($deliveries, function ($supplier) {
            return $supplier['days'] > 0;
        });

        if (empty($deliveries)) {
            return null;
        }

        $bestDelivery = min(array_column($deliveries, 'days'));
        $bestSupplier = array_search($bestDelivery, array_column($deliveries, 'days'));

        return $deliveries[$bestSupplier];
    }

    public function getComparisonSummaryAttribute()
    {
        $suppliers = [
            'supplier_1' => [
                'id' => $this->supplier_1_id,
                'price' => $this->supplier_1_price,
                'delivery_days' => $this->supplier_1_delivery_days,
                'quality_score' => $this->supplier_1_quality_score,
            ],
            'supplier_2' => [
                'id' => $this->supplier_2_id,
                'price' => $this->supplier_2_price,
                'delivery_days' => $this->supplier_2_delivery_days,
                'quality_score' => $this->supplier_2_quality_score,
            ],
            'supplier_3' => [
                'id' => $this->supplier_3_id,
                'price' => $this->supplier_3_price,
                'delivery_days' => $this->supplier_3_delivery_days,
                'quality_score' => $this->supplier_3_quality_score,
            ],
        ];

        // Filter out suppliers with no data
        $suppliers = array_filter($suppliers, function ($supplier) {
            return $supplier['price'] > 0;
        });

        if (empty($suppliers)) {
            return null;
        }

        $prices = array_column($suppliers, 'price');
        $deliveries = array_column($suppliers, 'delivery_days');
        $qualities = array_column($suppliers, 'quality_score');

        return [
            'total_suppliers' => count($suppliers),
            'price_range' => [
                'min' => min($prices),
                'max' => max($prices),
                'avg' => array_sum($prices) / count($prices),
            ],
            'delivery_range' => [
                'min' => min($deliveries),
                'max' => max($deliveries),
                'avg' => array_sum($deliveries) / count($deliveries),
            ],
            'quality_range' => [
                'min' => min($qualities),
                'max' => max($qualities),
                'avg' => array_sum($qualities) / count($qualities),
            ],
            'best_price_supplier' => $this->best_price_supplier,
            'best_quality_supplier' => $this->best_quality_supplier,
            'best_delivery_supplier' => $this->best_delivery_supplier,
        ];
    }

    public function calculateRecommendedSupplier()
    {
        $suppliers = [
            'supplier_1' => [
                'id' => $this->supplier_1_id,
                'price' => $this->supplier_1_price,
                'delivery_days' => $this->supplier_1_delivery_days,
                'quality_score' => $this->supplier_1_quality_score,
            ],
            'supplier_2' => [
                'id' => $this->supplier_2_id,
                'price' => $this->supplier_2_price,
                'delivery_days' => $this->supplier_2_delivery_days,
                'quality_score' => $this->supplier_2_quality_score,
            ],
            'supplier_3' => [
                'id' => $this->supplier_3_id,
                'price' => $this->supplier_3_price,
                'delivery_days' => $this->supplier_3_delivery_days,
                'quality_score' => $this->supplier_3_quality_score,
            ],
        ];

        $suppliers = array_filter($suppliers, function ($supplier) {
            return $supplier['price'] > 0;
        });

        if (empty($suppliers)) {
            return null;
        }

        $scores = [];
        foreach ($suppliers as $key => $supplier) {
            // Normalize scores (lower is better for price and delivery)
            $priceScore = 100 - (($supplier['price'] / max(array_column($suppliers, 'price'))) * 100);
            $deliveryScore = 100 - (($supplier['delivery_days'] / max(array_column($suppliers, 'delivery_days'))) * 100);
            $qualityScore = ($supplier['quality_score'] / max(array_column($suppliers, 'quality_score'))) * 100;

            // Weighted score: 40% price, 30% delivery, 30% quality
            $totalScore = ($priceScore * 0.4) + ($deliveryScore * 0.3) + ($qualityScore * 0.3);
            $scores[$key] = $totalScore;
        }

        $bestSupplier = array_search(max($scores), $scores);
        $this->recommended_supplier_id = $suppliers[$bestSupplier]['id'];

        // Calculate potential cost savings
        $currentSupplierPrice = $suppliers[$bestSupplier]['price'];
        $otherPrices = array_column($suppliers, 'price');
        $otherPrices = array_filter($otherPrices, function ($price) use ($currentSupplierPrice) {
            return $price > $currentSupplierPrice;
        });

        $this->cost_savings_potential = !empty($otherPrices) ? max($otherPrices) - $currentSupplierPrice : 0;

        $this->save();
        return $this->recommended_supplier_id;
    }

    public static function createComparison($categoryId, $supplierIds, $comparisonDate = null)
    {
        $comparison = new self([
            'comparison_date' => $comparisonDate ?? now(),
            'product_category_id' => $categoryId,
            'supplier_1_id' => $supplierIds[0] ?? null,
            'supplier_2_id' => $supplierIds[1] ?? null,
            'supplier_3_id' => $supplierIds[2] ?? null,
        ]);

        // Get latest prices and performance data for each supplier
        foreach (['supplier_1', 'supplier_2', 'supplier_3'] as $supplierKey) {
            $supplierId = $comparison->{$supplierKey . '_id'};
            if ($supplierId) {
                $latestOrder = PurchaseOrder::where('vendor_id', $supplierId)
                    ->whereHas('lines', function ($query) use ($categoryId) {
                        $query->whereHas('inventoryItem', function ($q) use ($categoryId) {
                            $q->where('category_id', $categoryId);
                        });
                    })
                    ->latest('order_date')
                    ->first();

                if ($latestOrder) {
                    $comparison->{$supplierKey . '_price'} = $latestOrder->total_amount;
                    $comparison->{$supplierKey . '_delivery_days'} = $latestOrder->expected_delivery_date
                        ? $latestOrder->expected_delivery_date->diffInDays($latestOrder->order_date)
                        : 30; // Default 30 days

                    // Get quality score from supplier performance
                    $performance = SupplierPerformance::where('vendor_id', $supplierId)
                        ->latest('created_at')
                        ->first();

                    $comparison->{$supplierKey . '_quality_score'} = $performance ? $performance->quality_rating : 3.0;
                }
            }
        }

        $comparison->save();
        $comparison->calculateRecommendedSupplier();

        return $comparison;
    }
}
