<?php

namespace App\Services\Documents;

final class DocumentType
{
    public const SALES_QUOTATION = 'sales_quotation';

    public const SALES_ORDER = 'sales_order';

    public const DELIVERY_ORDER = 'delivery_order';

    public const SALES_INVOICE = 'sales_invoice';

    public const SALES_RECEIPT = 'sales_receipt';

    public const SALES_CREDIT_MEMO = 'sales_credit_memo';

    public const PURCHASE_ORDER = 'purchase_order';

    public const GOODS_RECEIPT_PO = 'goods_receipt_po';

    public const PURCHASE_INVOICE = 'purchase_invoice';

    public const PURCHASE_PAYMENT = 'purchase_payment';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SALES_QUOTATION,
            self::SALES_ORDER,
            self::DELIVERY_ORDER,
            self::SALES_INVOICE,
            self::SALES_RECEIPT,
            self::SALES_CREDIT_MEMO,
            self::PURCHASE_ORDER,
            self::GOODS_RECEIPT_PO,
            self::PURCHASE_INVOICE,
            self::PURCHASE_PAYMENT,
        ];
    }
}
