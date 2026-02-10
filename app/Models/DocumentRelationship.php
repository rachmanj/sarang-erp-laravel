<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentRelationship extends Model
{
    protected $fillable = [
        'source_document_type',
        'source_document_id',
        'target_document_type',
        'target_document_id',
        'relationship_type',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the source document (polymorphic relationship)
     */
    public function sourceDocument(): MorphTo
    {
        return $this->morphTo('source_document', 'source_document_type', 'source_document_id');
    }

    /**
     * Get the target document (polymorphic relationship)
     */
    public function targetDocument(): MorphTo
    {
        return $this->morphTo('target_document', 'target_document_type', 'target_document_id');
    }

    /**
     * Scope for base relationships
     */
    public function scopeBase($query)
    {
        return $query->where('relationship_type', 'base');
    }

    /**
     * Scope for target relationships
     */
    public function scopeTarget($query)
    {
        return $query->where('relationship_type', 'target');
    }

    /**
     * Scope for related relationships
     */
    public function scopeRelated($query)
    {
        return $query->where('relationship_type', 'related');
    }

    /**
     * Get document type mapping for permissions (supports both morph class and snake_case).
     * Values are permission base names; filterByUserPermissions appends '.view'.
     */
    public static function getDocumentPermissionMap(): array
    {
        return [
            'purchase_order' => 'purchase-orders',
            'goods_receipt_po' => 'purchase-orders',
            'purchase_invoice' => 'ap.invoices',
            'purchase_payment' => 'ap.payments',
            'sales_order' => 'sales-orders',
            'delivery_order' => 'sales-orders',
            'sales_invoice' => 'ar.invoices',
            'sales_receipt' => 'ar.receipts',
            'App\Models\PurchaseOrder' => 'purchase-orders',
            'App\Models\GoodsReceiptPO' => 'purchase-orders',
            'App\Models\Accounting\PurchaseInvoice' => 'ap.invoices',
            'App\Models\Accounting\PurchasePayment' => 'ap.payments',
            'App\Models\SalesOrder' => 'sales-orders',
            'App\Models\DeliveryOrder' => 'sales-orders',
            'App\Models\Accounting\SalesInvoice' => 'ar.invoices',
            'App\Models\Accounting\SalesReceipt' => 'ar.receipts',
        ];
    }

    /**
     * Get permission for document type
     */
    public static function getDocumentPermission(string $documentType): string
    {
        $permissions = self::getDocumentPermissionMap();
        return $permissions[$documentType] ?? 'documents.view';
    }
}