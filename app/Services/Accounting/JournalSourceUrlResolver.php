<?php

namespace App\Services\Accounting;

use App\Models\DocumentRelationship;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
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

    /** @var array<string, string> */
    private const TABLE_MAP = [
        'sales_invoice' => 'sales_invoices',
        'purchase_invoice' => 'purchase_invoices',
        'sales_receipt' => 'sales_receipts',
        'purchase_payment' => 'purchase_payments',
        'goods_receipt_po' => 'goods_receipt_po',
        'sales_order' => 'sales_orders',
        'delivery_order' => 'delivery_orders',
        'purchase_order' => 'purchase_orders',
    ];

    /** @var array<string, bool> */
    private static array $existenceCache = [];

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

        // Do not link to source documents that no longer exist (hard-deleted records
        // leave their journals behind as an audit trail, but the show page 404s).
        $table = self::TABLE_MAP[$sourceType] ?? null;
        if ($table) {
            $key = "{$table}:{$sourceId}";
            if (! array_key_exists($key, self::$existenceCache)) {
                self::$existenceCache[$key] = DB::table($table)->where('id', $sourceId)->exists();
            }
            if (! self::$existenceCache[$key]) {
                return null;
            }
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
