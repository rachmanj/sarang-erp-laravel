<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

final class DocumentOpenState
{
    public const DEFAULT_STATE = 'open';

    public const VALID_STATES = ['all', 'open', 'closed'];

    public const VALID_TYPES = [
        'sales_invoice',
        'purchase_invoice',
        'sales_order',
        'purchase_order',
        'delivery_order',
        'grpo',
        'sales_receipt',
        'purchase_payment',
    ];

    public static function normalizeState(?string $state): string
    {
        $state = strtolower(trim((string) $state));

        if ($state === '' || $state === 'all') {
            return 'all';
        }

        if (! in_array($state, ['open', 'closed'], true)) {
            return self::DEFAULT_STATE;
        }

        return $state;
    }

    public static function applyToQuery(Builder $query, string $type, string $alias, string $state): void
    {
        $state = self::normalizeState($state);

        if ($state === 'all') {
            return;
        }

        if (! in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported document type: {$type}");
        }

        if ($state === 'closed') {
            self::applyClosed($query, $type, $alias);

            return;
        }

        self::applyOpen($query, $type, $alias);
    }

    private static function applyClosed(Builder $query, string $type, string $alias): void
    {
        match ($type) {
            'sales_invoice' => self::applySalesInvoiceClosed($query, $alias),
            'purchase_invoice' => self::applyPurchaseInvoiceClosed($query, $alias),
            'sales_order' => self::applySalesOrderClosed($query, $alias),
            'purchase_order' => self::applyPurchaseOrderClosed($query, $alias),
            'delivery_order' => self::applyDeliveryOrderClosed($query, $alias),
            'grpo' => self::applyGrpoClosed($query, $alias),
            'sales_receipt', 'purchase_payment' => $query->where("{$alias}.status", 'posted'),
        };
    }

    private static function applyOpen(Builder $query, string $type, string $alias): void
    {
        match ($type) {
            'sales_invoice' => self::applySalesInvoiceOpen($query, $alias),
            'purchase_invoice' => self::applyPurchaseInvoiceOpen($query, $alias),
            'sales_order' => self::applySalesOrderOpen($query, $alias),
            'purchase_order' => self::applyPurchaseOrderOpen($query, $alias),
            'delivery_order' => self::applyDeliveryOrderOpen($query, $alias),
            'grpo' => self::applyGrpoOpen($query, $alias),
            'sales_receipt', 'purchase_payment' => $query->where("{$alias}.status", 'draft'),
        };
    }

    private static function applySalesInvoiceClosed(Builder $query, string $alias): void
    {
        $query->where("{$alias}.status", 'posted')
            ->whereRaw(
                "{$alias}.total_amount - COALESCE((SELECT SUM(sra.amount) FROM sales_receipt_allocations sra WHERE sra.invoice_id = {$alias}.id), 0) <= 0.01"
            );
    }

    private static function applySalesInvoiceOpen(Builder $query, string $alias): void
    {
        $query->where(function (Builder $q) use ($alias) {
            $q->where("{$alias}.status", '!=', 'posted')
                ->orWhereRaw(
                    "{$alias}.total_amount - COALESCE((SELECT SUM(sra.amount) FROM sales_receipt_allocations sra WHERE sra.invoice_id = {$alias}.id), 0) > 0.01"
                );
        });
    }

    private static function applyPurchaseInvoiceClosed(Builder $query, string $alias): void
    {
        $query->where("{$alias}.status", 'posted')
            ->whereRaw(
                "{$alias}.total_amount - COALESCE((SELECT SUM(ppa.amount) FROM purchase_payment_allocations ppa WHERE ppa.invoice_id = {$alias}.id), 0) <= 0.01"
            );
    }

    private static function applyPurchaseInvoiceOpen(Builder $query, string $alias): void
    {
        $query->where(function (Builder $q) use ($alias) {
            $q->where("{$alias}.status", '!=', 'posted')
                ->orWhereRaw(
                    "{$alias}.total_amount - COALESCE((SELECT SUM(ppa.amount) FROM purchase_payment_allocations ppa WHERE ppa.invoice_id = {$alias}.id), 0) > 0.01"
                );
        });
    }

    private static function applySalesOrderClosed(Builder $query, string $alias): void
    {
        $query->whereExists(function (Builder $sub) use ($alias) {
            $sub->selectRaw('1')
                ->from('sales_order_lines as sol')
                ->whereColumn('sol.order_id', "{$alias}.id");
        })->whereNotExists(function (Builder $sub) use ($alias) {
            $sub->selectRaw('1')
                ->from('sales_order_lines as sol')
                ->whereColumn('sol.order_id', "{$alias}.id")
                ->whereColumn('sol.delivered_qty', '<', 'sol.qty');
        });
    }

    private static function applySalesOrderOpen(Builder $query, string $alias): void
    {
        $query->where(function (Builder $q) use ($alias) {
            $q->whereNotExists(function (Builder $sub) use ($alias) {
                $sub->selectRaw('1')
                    ->from('sales_order_lines as sol')
                    ->whereColumn('sol.order_id', "{$alias}.id");
            })->orWhereExists(function (Builder $sub) use ($alias) {
                $sub->selectRaw('1')
                    ->from('sales_order_lines as sol')
                    ->whereColumn('sol.order_id', "{$alias}.id")
                    ->whereColumn('sol.delivered_qty', '<', 'sol.qty');
            });
        });
    }

    private static function applyPurchaseOrderClosed(Builder $query, string $alias): void
    {
        $query->whereExists(function (Builder $sub) use ($alias) {
            $sub->selectRaw('1')
                ->from('purchase_order_lines as pol')
                ->whereColumn('pol.order_id', "{$alias}.id");
        })->whereNotExists(function (Builder $sub) use ($alias) {
            $sub->selectRaw('1')
                ->from('purchase_order_lines as pol')
                ->whereColumn('pol.order_id', "{$alias}.id")
                ->whereColumn('pol.received_qty', '<', 'pol.qty');
        });
    }

    private static function applyPurchaseOrderOpen(Builder $query, string $alias): void
    {
        $query->where(function (Builder $q) use ($alias) {
            $q->whereNotExists(function (Builder $sub) use ($alias) {
                $sub->selectRaw('1')
                    ->from('purchase_order_lines as pol')
                    ->whereColumn('pol.order_id', "{$alias}.id");
            })->orWhereExists(function (Builder $sub) use ($alias) {
                $sub->selectRaw('1')
                    ->from('purchase_order_lines as pol')
                    ->whereColumn('pol.order_id', "{$alias}.id")
                    ->whereColumn('pol.received_qty', '<', 'pol.qty');
            });
        });
    }

    private static function applyDeliveryOrderClosed(Builder $query, string $alias): void
    {
        $query->where(function (Builder $q) use ($alias) {
            $q->whereIn("{$alias}.status", ['cancelled', 'reversed'])
                ->orWhere(function (Builder $inner) use ($alias) {
                    $inner->whereExists(function (Builder $sub) use ($alias) {
                        $sub->selectRaw('1')
                            ->from('delivery_order_lines as dol')
                            ->whereColumn('dol.delivery_order_id', "{$alias}.id")
                            ->where('dol.delivered_qty', '>', 0);
                    })->whereNotExists(function (Builder $sub) use ($alias) {
                        $sub->selectRaw('1')
                            ->from('delivery_order_lines as dol')
                            ->whereColumn('dol.delivery_order_id', "{$alias}.id")
                            ->whereRaw(
                                'dol.delivered_qty > COALESCE((SELECT SUM(sil.qty) FROM sales_invoice_lines sil WHERE sil.delivery_order_line_id = dol.id), 0)'
                            );
                    });
                });
        });
    }

    private static function applyDeliveryOrderOpen(Builder $query, string $alias): void
    {
        $query->whereNotIn("{$alias}.status", ['cancelled', 'reversed'])
            ->where(function (Builder $q) use ($alias) {
                $q->whereNotExists(function (Builder $sub) use ($alias) {
                    $sub->selectRaw('1')
                        ->from('delivery_order_lines as dol')
                        ->whereColumn('dol.delivery_order_id', "{$alias}.id")
                        ->where('dol.delivered_qty', '>', 0);
                })->orWhereExists(function (Builder $sub) use ($alias) {
                    $sub->selectRaw('1')
                        ->from('delivery_order_lines as dol')
                        ->whereColumn('dol.delivery_order_id', "{$alias}.id")
                        ->whereRaw(
                            'dol.delivered_qty > COALESCE((SELECT SUM(sil.qty) FROM sales_invoice_lines sil WHERE sil.delivery_order_line_id = dol.id), 0)'
                        );
                });
            });
    }

    private static function applyGrpoClosed(Builder $query, string $alias): void
    {
        $query->where(function (Builder $q) use ($alias) {
            $q->whereExists(function (Builder $sub) use ($alias) {
                $sub->selectRaw('1')
                    ->from('goods_receipt_po_purchase_invoice as grp')
                    ->whereColumn('grp.grpo_id', "{$alias}.id");
            })->orWhereExists(function (Builder $sub) use ($alias) {
                $sub->selectRaw('1')
                    ->from('purchase_invoices as pi')
                    ->whereColumn('pi.goods_receipt_id', "{$alias}.id");
            });
        });
    }

    private static function applyGrpoOpen(Builder $query, string $alias): void
    {
        $query->whereNotExists(function (Builder $sub) use ($alias) {
            $sub->selectRaw('1')
                ->from('goods_receipt_po_purchase_invoice as grp')
                ->whereColumn('grp.grpo_id', "{$alias}.id");
        })->whereNotExists(function (Builder $sub) use ($alias) {
            $sub->selectRaw('1')
                ->from('purchase_invoices as pi')
                ->whereColumn('pi.goods_receipt_id', "{$alias}.id");
        });
    }
}
