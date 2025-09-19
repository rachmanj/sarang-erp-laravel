<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\BusinessPartner;
use App\Models\CustomerItemPriceLevel;
use Illuminate\Support\Facades\DB;

class PriceLevelService
{
    /**
     * Get effective price for an item and customer
     */
    public function getEffectivePrice($itemId, $customerId = null, $priceLevel = null)
    {
        $item = InventoryItem::findOrFail($itemId);

        // If no price level specified, get from customer
        if (!$priceLevel && $customerId) {
            $customer = BusinessPartner::find($customerId);
            $priceLevel = $customer ? $customer->default_sales_price_level : '1';
        }

        // Check for customer-specific price level
        if ($customerId) {
            $customerPriceLevel = CustomerItemPriceLevel::where('business_partner_id', $customerId)
                ->where('inventory_item_id', $itemId)
                ->first();

            if ($customerPriceLevel) {
                return $customerPriceLevel->getEffectivePrice();
            }
        }

        return $item->getPriceForLevel($priceLevel ?? '1', $customerId);
    }

    /**
     * Set customer price level for an item
     */
    public function setCustomerItemPriceLevel($customerId, $itemId, $priceLevel, $customPrice = null)
    {
        return DB::transaction(function () use ($customerId, $itemId, $priceLevel, $customPrice) {
            $customerPriceLevel = CustomerItemPriceLevel::updateOrCreate(
                [
                    'business_partner_id' => $customerId,
                    'inventory_item_id' => $itemId,
                ],
                [
                    'price_level' => $priceLevel,
                    'custom_price' => $customPrice,
                ]
            );

            return $customerPriceLevel;
        });
    }

    /**
     * Remove customer price level for an item
     */
    public function removeCustomerItemPriceLevel($customerId, $itemId)
    {
        return CustomerItemPriceLevel::where('business_partner_id', $customerId)
            ->where('inventory_item_id', $itemId)
            ->delete();
    }

    /**
     * Set customer default price level
     */
    public function setCustomerDefaultPriceLevel($customerId, $priceLevel)
    {
        $customer = BusinessPartner::findOrFail($customerId);
        $customer->update(['default_sales_price_level' => $priceLevel]);

        return $customer;
    }

    /**
     * Calculate price levels based on percentages
     */
    public function calculatePriceLevels($itemId, $level2Percentage = null, $level3Percentage = null)
    {
        $item = InventoryItem::findOrFail($itemId);

        $level2Price = null;
        $level3Price = null;

        if ($level2Percentage !== null) {
            $level2Price = $item->selling_price * (1 + $level2Percentage / 100);
        }

        if ($level3Percentage !== null) {
            $level3Price = $item->selling_price * (1 + $level3Percentage / 100);
        }

        $item->update([
            'price_level_2_percentage' => $level2Percentage,
            'price_level_3_percentage' => $level3Percentage,
            'selling_price_level_2' => $level2Price,
            'selling_price_level_3' => $level3Price,
        ]);

        return $item;
    }

    /**
     * Get price level summary for an item
     */
    public function getItemPriceLevelSummary($itemId)
    {
        $item = InventoryItem::with('customerPriceLevels.businessPartner')->findOrFail($itemId);

        $priceLevels = [
            '1' => $item->selling_price,
            '2' => $item->getPriceForLevel('2'),
            '3' => $item->getPriceForLevel('3'),
        ];

        $customerOverrides = $item->customerPriceLevels->map(function ($override) {
            return [
                'customer' => $override->businessPartner->name,
                'price_level' => $override->price_level,
                'custom_price' => $override->custom_price,
                'effective_price' => $override->getEffectivePrice(),
            ];
        });

        return [
            'item' => $item,
            'price_levels' => $priceLevels,
            'customer_overrides' => $customerOverrides,
        ];
    }

    /**
     * Get customer price level summary
     */
    public function getCustomerPriceLevelSummary($customerId)
    {
        $customer = BusinessPartner::with('itemPriceLevels.inventoryItem')->findOrFail($customerId);

        $defaultPriceLevel = $customer->default_sales_price_level;
        $itemOverrides = $customer->itemPriceLevels->map(function ($override) {
            return [
                'item' => $override->inventoryItem->name,
                'price_level' => $override->price_level,
                'custom_price' => $override->custom_price,
                'effective_price' => $override->getEffectivePrice(),
            ];
        });

        return [
            'customer' => $customer,
            'default_price_level' => $defaultPriceLevel,
            'item_overrides' => $itemOverrides,
        ];
    }
}
