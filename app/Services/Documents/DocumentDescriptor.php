<?php

namespace App\Services\Documents;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\SalesCreditMemo;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use InvalidArgumentException;

final class DocumentDescriptor
{
    /** @var array<string, array{class: class-string, number_column: string, date_column: string, permission: string, route_prefix: string, label: string, journal_source_types: list<string>}> */
    private const REGISTRY = [
        DocumentType::SALES_QUOTATION => [
            'class' => SalesQuotation::class,
            'number_column' => 'quotation_no',
            'date_column' => 'date',
            'permission' => 'ar.quotations.delete',
            'route_prefix' => 'sales-quotations',
            'label' => 'Sales Quotation',
            'journal_source_types' => [],
        ],
        DocumentType::SALES_ORDER => [
            'class' => SalesOrder::class,
            'number_column' => 'order_no',
            'date_column' => 'date',
            'permission' => 'sales-orders.delete',
            'route_prefix' => 'sales-orders',
            'label' => 'Sales Order',
            'journal_source_types' => [],
        ],
        DocumentType::DELIVERY_ORDER => [
            'class' => DeliveryOrder::class,
            'number_column' => 'do_number',
            'date_column' => 'created_at',
            'permission' => 'delivery-orders.delete',
            'route_prefix' => 'delivery-orders',
            'label' => 'Delivery Order',
            'journal_source_types' => [DeliveryOrder::class],
        ],
        DocumentType::SALES_INVOICE => [
            'class' => SalesInvoice::class,
            'number_column' => 'invoice_no',
            'date_column' => 'date',
            'permission' => 'ar.invoices.delete',
            'route_prefix' => 'sales-invoices',
            'label' => 'Sales Invoice',
            'journal_source_types' => ['sales_invoice'],
        ],
        DocumentType::SALES_RECEIPT => [
            'class' => SalesReceipt::class,
            'number_column' => 'receipt_no',
            'date_column' => 'date',
            'permission' => 'ar.receipts.delete',
            'route_prefix' => 'sales-receipts',
            'label' => 'Sales Receipt',
            'journal_source_types' => ['sales_receipt'],
        ],
        DocumentType::SALES_CREDIT_MEMO => [
            'class' => SalesCreditMemo::class,
            'number_column' => 'memo_no',
            'date_column' => 'date',
            'permission' => 'ar.credit-memos.delete',
            'route_prefix' => 'sales-credit-memos',
            'label' => 'Sales Credit Memo',
            'journal_source_types' => ['sales_credit_memo'],
        ],
        DocumentType::PURCHASE_ORDER => [
            'class' => PurchaseOrder::class,
            'number_column' => 'order_no',
            'date_column' => 'date',
            'permission' => 'purchase-orders.delete',
            'route_prefix' => 'purchase-orders',
            'label' => 'Purchase Order',
            'journal_source_types' => [],
        ],
        DocumentType::GOODS_RECEIPT_PO => [
            'class' => GoodsReceiptPO::class,
            'number_column' => 'grn_no',
            'date_column' => 'date',
            'permission' => 'goods-receipt-pos.delete',
            'route_prefix' => 'goods-receipt-pos',
            'label' => 'Goods Receipt PO',
            'journal_source_types' => ['goods_receipt_po'],
        ],
        DocumentType::PURCHASE_INVOICE => [
            'class' => PurchaseInvoice::class,
            'number_column' => 'invoice_no',
            'date_column' => 'date',
            'permission' => 'ap.invoices.delete',
            'route_prefix' => 'purchase-invoices',
            'label' => 'Purchase Invoice',
            'journal_source_types' => ['purchase_invoice'],
        ],
        DocumentType::PURCHASE_PAYMENT => [
            'class' => PurchasePayment::class,
            'number_column' => 'payment_no',
            'date_column' => 'date',
            'permission' => 'ap.payments.delete',
            'route_prefix' => 'purchase-payments',
            'label' => 'Purchase Payment',
            'journal_source_types' => ['purchase_payment'],
        ],
    ];

    public static function assertValid(string $type): void
    {
        if (! isset(self::REGISTRY[$type])) {
            throw new InvalidArgumentException("Unknown document type: {$type}");
        }
    }

    /** @return class-string */
    public static function modelClass(string $type): string
    {
        self::assertValid($type);

        return self::REGISTRY[$type]['class'];
    }

    public static function numberColumn(string $type): string
    {
        self::assertValid($type);

        return self::REGISTRY[$type]['number_column'];
    }

    public static function dateColumn(string $type): string
    {
        self::assertValid($type);

        return self::REGISTRY[$type]['date_column'];
    }

    public static function permission(string $type): string
    {
        self::assertValid($type);

        return self::REGISTRY[$type]['permission'];
    }

    public static function routePrefix(string $type): string
    {
        self::assertValid($type);

        return self::REGISTRY[$type]['route_prefix'];
    }

    public static function label(string $type): string
    {
        self::assertValid($type);

        return self::REGISTRY[$type]['label'];
    }

    /** @return list<string> */
    public static function journalSourceTypes(string $type): array
    {
        self::assertValid($type);

        return self::REGISTRY[$type]['journal_source_types'];
    }

    public static function typeForModel(object $model): string
    {
        foreach (self::REGISTRY as $type => $meta) {
            if ($model instanceof $meta['class']) {
                return $type;
            }
        }

        throw new InvalidArgumentException('Model is not a supported deletable document.');
    }
}
