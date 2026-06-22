<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\DeliveryOrder;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class DeliveryOrderJournalBuilder
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function buildRevenueRecognition(DeliveryOrder $deliveryOrder): JournalDraft
    {
        if ($deliveryOrder->status !== 'delivered') {
            throw new \Exception('Delivery Order must be delivered before creating revenue recognition journal entry.');
        }

        $deliveryOrder->loadMissing(['lines.inventoryItem']);

        $lines = [];
        $totalRevenue = 0.0;

        foreach ($deliveryOrder->lines as $line) {
            if ($line->inventoryItem && $line->delivered_qty > 0) {
                $deliveredAmount = ($line->delivered_qty / $line->ordered_qty) * $line->amount;
                $unitCost = $this->inventoryService->calculateUnitCost($line->inventoryItem);
                $cogsAmount = $line->delivered_qty * $unitCost;

                if ($deliveredAmount > 0) {
                    $lines[] = [
                        'account_id' => $this->getSalesRevenueAccount(),
                        'debit' => 0,
                        'credit' => $deliveredAmount,
                        'project_id' => $line->project_id ?? null,
                        'dept_id' => $line->dept_id ?? null,
                        'memo' => "Revenue from DO {$deliveryOrder->do_number} - {$line->item_name}",
                    ];
                }

                if ($cogsAmount > 0) {
                    $lines[] = [
                        'account_id' => $this->getCOGSAccount(),
                        'debit' => $cogsAmount,
                        'credit' => 0,
                        'project_id' => $line->project_id ?? null,
                        'dept_id' => $line->dept_id ?? null,
                        'memo' => "COGS for DO {$deliveryOrder->do_number} - {$line->item_name}",
                    ];

                    $lines[] = [
                        'account_id' => $this->getInventoryReservedAccount(),
                        'debit' => 0,
                        'credit' => $cogsAmount,
                        'project_id' => $line->project_id ?? null,
                        'dept_id' => $line->dept_id ?? null,
                        'memo' => "Release reserved inventory - DO {$deliveryOrder->do_number} - {$line->item_name}",
                    ];
                }

                $totalRevenue += $deliveredAmount;
            }
        }

        if ($lines === []) {
            throw new \Exception('No delivered items found in delivery order for revenue recognition.');
        }

        $lines[] = [
            'account_id' => $this->getARUnInvoiceAccount(),
            'debit' => $totalRevenue,
            'credit' => 0,
            'memo' => "AR UnInvoice - DO {$deliveryOrder->do_number}",
        ];

        return new JournalDraft(
            description: "Revenue Recognition - DO {$deliveryOrder->do_number}",
            lines: $lines,
            date: $deliveryOrder->actual_delivery_date?->toDateString() ?? now()->toDateString(),
        );
    }

    private function getInventoryReservedAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.3.01')
            ->orWhere('name', 'like', '%Inventory Reserved%')
            ->first();

        if (! $account) {
            throw new \Exception('Inventory Reserved account not found. Please create account with code 1.1.3.01');
        }

        return (int) $account->id;
    }

    private function getSalesRevenueAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '4.1.1.01')
            ->orWhere('name', 'like', '%Penjualan Stationery%')
            ->first();

        if (! $account) {
            throw new \Exception('Sales Revenue account not found. Please create account with code 4.1.1.01');
        }

        return (int) $account->id;
    }

    private function getCOGSAccount(): int
    {
        $account = DB::table('accounts')
            ->where(function ($q) {
                $q->where('code', '5.1.01')
                    ->orWhere('name', 'like', '%HPP Stationery%');
            })
            ->first();

        if (! $account) {
            throw new \Exception('Cost of Goods Sold account not found. Please create account with code 5.1.01');
        }

        return (int) $account->id;
    }

    private function getARUnInvoiceAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.2.04')
            ->first();

        if (! $account) {
            throw new \Exception('AR UnInvoice account not found. Please create account with code 1.1.2.04');
        }

        return (int) $account->id;
    }
}
