<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use Illuminate\Support\Facades\DB;
use Exception;

class DeliveryJournalService
{
    public function __construct(
        private PostingService $postingService,
        private DocumentNumberingService $documentNumberingService
    ) {}

    /**
     * Create inventory reservation journal entry when DO is approved
     */
    public function createInventoryReservation(DeliveryOrder $deliveryOrder): int
    {
        if ($deliveryOrder->approval_status !== 'approved') {
            throw new Exception('Delivery Order must be approved before creating inventory reservation journal entry.');
        }

        $lines = [];

        foreach ($deliveryOrder->lines as $line) {
            if ($line->inventoryItem && $line->inventoryItem->item_type === 'item') {
                // Reserve inventory (move from available to reserved)
                $lines[] = [
                    'account_id' => $this->getInventoryReservedAccount(),
                    'debit' => $line->amount,
                    'credit' => 0,
                    'project_id' => $line->project_id ?? null,
                    'dept_id' => $line->dept_id ?? null,
                    'memo' => "Reserve inventory for DO {$deliveryOrder->do_number} - {$line->item_name}",
                ];

                $lines[] = [
                    'account_id' => $this->getInventoryAvailableAccount(),
                    'debit' => 0,
                    'credit' => $line->amount,
                    'project_id' => $line->project_id ?? null,
                    'dept_id' => $line->dept_id ?? null,
                    'memo' => "Release from available stock - {$line->item_name}",
                ];
            }
        }

        if (empty($lines)) {
            throw new Exception('No inventory items found in delivery order for reservation.');
        }

        return $this->postingService->postJournal([
            'date' => $deliveryOrder->approved_at->toDateString(),
            'description' => "Inventory Reservation - DO {$deliveryOrder->do_number}",
            'source_type' => DeliveryOrder::class,
            'source_id' => $deliveryOrder->id,
            'posted_by' => $deliveryOrder->approved_by,
            'lines' => $lines,
        ]);
    }

    /**
     * Create revenue recognition journal entry when items are delivered
     */
    public function createRevenueRecognition(DeliveryOrder $deliveryOrder): int
    {
        if ($deliveryOrder->status !== 'delivered') {
            throw new Exception('Delivery Order must be delivered before creating revenue recognition journal entry.');
        }

        $lines = [];
        $totalRevenue = 0;
        $totalCOGS = 0;

        foreach ($deliveryOrder->lines as $line) {
            if ($line->inventoryItem && $line->delivered_qty > 0) {
                $deliveredAmount = ($line->delivered_qty / $line->ordered_qty) * $line->amount;

                // Revenue recognition
                $lines[] = [
                    'account_id' => $this->getSalesRevenueAccount(),
                    'debit' => 0,
                    'credit' => $deliveredAmount,
                    'project_id' => $line->project_id ?? null,
                    'dept_id' => $line->dept_id ?? null,
                    'memo' => "Revenue from DO {$deliveryOrder->do_number} - {$line->item_name}",
                ];

                // COGS recognition
                $cogsAmount = $this->calculateCOGS($line, $deliveredAmount);
                $lines[] = [
                    'account_id' => $this->getCOGSAccount(),
                    'debit' => $cogsAmount,
                    'credit' => 0,
                    'project_id' => $line->project_id ?? null,
                    'dept_id' => $line->dept_id ?? null,
                    'memo' => "COGS for DO {$deliveryOrder->do_number} - {$line->item_name}",
                ];

                $totalRevenue += $deliveredAmount;
                $totalCOGS += $cogsAmount;
            }
        }

        if (empty($lines)) {
            throw new Exception('No delivered items found in delivery order for revenue recognition.');
        }

        // AR UnInvoice recognition (goods delivered but not yet invoiced)
        $lines[] = [
            'account_id' => $this->getARUnInvoiceAccount(),
            'debit' => $totalRevenue,
            'credit' => 0,
            'memo' => "AR UnInvoice - DO {$deliveryOrder->do_number}",
        ];

        return $this->postingService->postJournal([
            'date' => $deliveryOrder->actual_delivery_date->toDateString(),
            'description' => "Revenue Recognition - DO {$deliveryOrder->do_number}",
            'source_type' => DeliveryOrder::class,
            'source_id' => $deliveryOrder->id,
            'lines' => $lines,
        ]);
    }

    /**
     * Reverse inventory reservation when DO is cancelled
     */
    public function reverseInventoryReservation(DeliveryOrder $deliveryOrder): int
    {
        // Find the original reservation journal
        $reservationJournal = DB::table('journals')
            ->where('source_type', DeliveryOrder::class)
            ->where('source_id', $deliveryOrder->id)
            ->where('description', 'like', '%Inventory Reservation%')
            ->first();

        if (!$reservationJournal) {
            throw new Exception('No inventory reservation journal found for this delivery order.');
        }

        return $this->postingService->reverseJournal(
            $reservationJournal->id,
            now()->toDateString(),
            auth()->id()
        );
    }

    /**
     * Get inventory reserved account ID
     */
    private function getInventoryReservedAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.3.01') // Inventory Reserved
            ->orWhere('name', 'like', '%Inventory Reserved%')
            ->first();

        if (!$account) {
            throw new Exception('Inventory Reserved account not found. Please create account with code 1.1.3.01');
        }

        return $account->id;
    }

    /**
     * Get inventory available account ID
     */
    private function getInventoryAvailableAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.3.02') // Inventory Available
            ->orWhere('name', 'like', '%Inventory Available%')
            ->first();

        if (!$account) {
            throw new Exception('Inventory Available account not found. Please create account with code 1.1.3.02');
        }

        return $account->id;
    }

    /**
     * Get sales revenue account ID
     */
    private function getSalesRevenueAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '4.1.1') // Sales Revenue
            ->orWhere('name', 'like', '%Sales Revenue%')
            ->first();

        if (!$account) {
            throw new Exception('Sales Revenue account not found. Please create account with code 4.1.1');
        }

        return $account->id;
    }

    /**
     * Get COGS account ID
     */
    private function getCOGSAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '5.1.1') // Cost of Goods Sold
            ->orWhere('name', 'like', '%Cost of Goods Sold%')
            ->first();

        if (!$account) {
            throw new Exception('Cost of Goods Sold account not found. Please create account with code 5.1.1');
        }

        return $account->id;
    }

    /**
     * Get AR UnInvoice account ID
     */
    private function getARUnInvoiceAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.2.04') // AR UnInvoice
            ->first();

        if (!$account) {
            throw new Exception('AR UnInvoice account not found. Please create account with code 1.1.2.04');
        }

        return $account->id;
    }

    /**
     * Get accounts receivable account ID
     */
    private function getAccountsReceivableAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.2.01') // Piutang Dagang
            ->first();

        if (!$account) {
            throw new Exception('Accounts Receivable account not found. Please create account with code 1.1.2.01');
        }

        return $account->id;
    }

    /**
     * Calculate COGS amount for a delivery line
     */
    private function calculateCOGS(DeliveryOrderLine $line, float $deliveredAmount): float
    {
        // For now, use a simple calculation based on delivered amount
        // In a real system, this would use FIFO/LIFO/Average costing methods
        $deliveredQty = $line->delivered_qty;
        $orderedQty = $line->ordered_qty;

        // Calculate COGS as percentage of delivered amount
        // This is a simplified approach - in production, you'd use actual inventory costing
        $cogsPercentage = 0.6; // Assume 60% of revenue is COGS

        return $deliveredAmount * $cogsPercentage;
    }
}
