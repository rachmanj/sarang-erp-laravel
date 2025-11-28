<?php

return [
    'enabled' => env('AUDIT_LOG_ENABLED', true),

    'default_ignored_fields' => [
        'updated_at',
        'created_at',
    ],

    'default_sensitive_fields' => [
        'password',
        'api_token',
        'remember_token',
        'secret',
    ],

    'queue' => env('AUDIT_LOG_QUEUE', false),

    'queue_connection' => env('AUDIT_LOG_QUEUE_CONNECTION', 'default'),

    'observed_models' => [
        \App\Models\PurchaseOrder::class,
        \App\Models\Accounting\PurchaseInvoice::class,
        \App\Models\Accounting\PurchasePayment::class,
        \App\Models\SalesOrder::class,
        \App\Models\Accounting\SalesInvoice::class,
        \App\Models\Accounting\SalesReceipt::class,
        \App\Models\DeliveryOrder::class,
        \App\Models\GoodsReceiptPO::class,
        \App\Models\Accounting\Journal::class,
        \App\Models\Accounting\JournalLine::class,

        \App\Models\BusinessPartner::class,
        \App\Models\InventoryItem::class,
        \App\Models\Warehouse::class,
        \App\Models\Dimensions\Project::class,
        \App\Models\Dimensions\Department::class,
        \App\Models\Asset::class,
        \App\Models\AssetCategory::class,

        \App\Models\User::class,
        \App\Models\Accounting\Account::class,
        \App\Models\ProductCategory::class,
    ],
];

