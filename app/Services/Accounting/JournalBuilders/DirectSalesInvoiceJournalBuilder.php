<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\SalesInvoice;
use App\Services\Accounting\HeaderDiscountAllocation;
use App\Services\Accounting\SalesInvoicePostingMath;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class DirectSalesInvoiceJournalBuilder
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function build(SalesInvoice $invoice): JournalDraft
    {
        $invoice->loadMissing(['lines.inventoryItem']);

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $ppnOutputId = (int) DB::table('accounts')->where('code', '2.1.2.01')->value('id');
        $wtaxPrepaidId = (int) DB::table('accounts')->where('code', '1.1.4.02')->value('id');
        $cogsAccountId = $this->getCOGSAccount();
        $inventoryAvailableAccountId = $this->getInventoryAvailableAccount();

        $scaledLines = HeaderDiscountAllocation::salesInvoiceLineScaled($invoice);
        $scaledByLineId = collect($scaledLines)->keyBy('line_id');

        $lines = [];
        $totalCogs = 0.0;
        $ppnTotal = 0.0;
        $wtaxTotal = 0.0;

        foreach ($invoice->lines as $line) {
            $scaled = $scaledByLineId->get($line->id);
            if (! $scaled) {
                continue;
            }

            $dpp = (float) $scaled['dpp'];
            $outputVat = (float) $scaled['output_vat'];
            $wtax = (float) $scaled['wtax'];

            if ($dpp > 0) {
                $lines[] = [
                    'account_id' => (int) $line->account_id,
                    'debit' => 0,
                    'credit' => $dpp,
                    'project_id' => $line->project_id,
                    'dept_id' => $line->dept_id,
                    'memo' => 'Direct sale revenue - '.($line->item_name ?? $line->description ?? 'Line'),
                ];
            }

            $ppnTotal += $outputVat;
            $wtaxTotal += $wtax;

            if ($line->inventory_item_id && $line->inventoryItem && $line->inventoryItem->item_type !== 'service') {
                $qty = (int) round((float) $line->qty);
                if ($qty > 0) {
                    $unitCost = $this->inventoryService->calculateUnitCost($line->inventoryItem);
                    $cogsAmount = round($qty * $unitCost, 2);
                    if ($cogsAmount > 0) {
                        $totalCogs += $cogsAmount;
                        $lines[] = [
                            'account_id' => $cogsAccountId,
                            'debit' => $cogsAmount,
                            'credit' => 0,
                            'project_id' => $line->project_id,
                            'dept_id' => $line->dept_id,
                            'memo' => 'COGS - Direct sale '.$invoice->invoice_no.' - '.($line->item_name ?? 'Item'),
                        ];
                    }
                }
            }
        }

        if ($ppnTotal > 0) {
            $lines[] = [
                'account_id' => $ppnOutputId,
                'debit' => 0,
                'credit' => round($ppnTotal, 2),
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Keluaran',
            ];
        }

        if ($totalCogs > 0) {
            $lines[] = [
                'account_id' => $inventoryAvailableAccountId,
                'debit' => 0,
                'credit' => round($totalCogs, 2),
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Release inventory - Direct sale '.$invoice->invoice_no,
            ];
        }

        $footer = SalesInvoicePostingMath::invoiceFooterTotals($invoice);
        $arAmount = (float) $footer['amount_due'];

        $lines[] = [
            'account_id' => $arAccountId,
            'debit' => $arAmount,
            'credit' => 0,
            'project_id' => null,
            'dept_id' => null,
            'memo' => 'Accounts Receivable - Direct sale',
        ];

        if ($wtaxTotal > 0 && $wtaxPrepaidId) {
            $lines[] = [
                'account_id' => $wtaxPrepaidId,
                'debit' => round($wtaxTotal, 2),
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'PPh 23 Dibayar Dimuka (customer withholding)',
            ];
        }

        return new JournalDraft(
            description: 'Post Direct Sale Invoice #'.$invoice->invoice_no,
            lines: $lines,
            date: $invoice->date->toDateString(),
        );
    }

    private function getInventoryAvailableAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.3.02')
            ->orWhere('name', 'like', '%Inventory Available%')
            ->first();

        if (! $account) {
            throw new \Exception('Inventory Available account not found. Please create account with code 1.1.3.02');
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
}
