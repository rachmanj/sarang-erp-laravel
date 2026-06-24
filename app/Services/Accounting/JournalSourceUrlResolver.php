<?php

namespace App\Services\Accounting;

use App\Models\DocumentRelationship;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

class JournalSourceUrlResolver
{
    /** @var array<string, string> */
    private const ROUTE_MAP = [
        'sales_invoice' => 'sales-invoices.show',
        'purchase_invoice' => 'purchase-invoices.show',
        'sales_receipt' => 'sales-receipts.show',
        'purchase_payment' => 'purchase-payments.show',
        'goods_receipt_po' => 'goods-receipt-pos.show',
        'sales_order' => 'sales-orders.show',
        'delivery_order' => 'delivery-orders.show',
        'purchase_order' => 'purchase-orders.show',
    ];

    public function resolve(?string $sourceType, ?int $sourceId, ?Authenticatable $user = null): ?string
    {
        if (! $sourceType || ! $sourceId) {
            return null;
        }

        $routeName = self::ROUTE_MAP[$sourceType] ?? null;
        if (! $routeName || ! Route::has($routeName)) {
            return null;
        }

        $permissionBase = DocumentRelationship::getDocumentPermissionMap()[$sourceType] ?? null;
        if ($permissionBase && $user && ! $user->can("{$permissionBase}.view")) {
            return null;
        }

        return route($routeName, $sourceId);
    }

    public function label(?string $sourceType, ?int $sourceId, ?string $journalNo = null): string
    {
        if ($sourceType && $sourceId) {
            return str_replace('_', ' ', ucwords($sourceType, '_')).' #'.$sourceId;
        }

        return $journalNo ?: '—';
    }
}
