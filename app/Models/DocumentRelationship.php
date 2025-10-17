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
     * Get document type mapping for permissions
     */
    public static function getDocumentPermissionMap(): array
    {
        return [
            'purchase_order' => 'po.orders',
            'goods_receipt_po' => 'po.receipts',
            'purchase_invoice' => 'ap.invoices',
            'purchase_payment' => 'ap.payments',
            'sales_order' => 'so.orders',
            'delivery_order' => 'so.deliveries',
            'sales_invoice' => 'ar.invoices',
            'sales_receipt' => 'ar.receipts',
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