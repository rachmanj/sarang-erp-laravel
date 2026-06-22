<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use Illuminate\Support\Facades\DB;

class GrpoJournalBuilder
{
    public function build(GoodsReceiptPO $grpo): JournalDraft
    {
        $grpo->loadMissing(['lines.item.category']);

        $inventoryByAccount = [];
        $totalAmount = 0.0;

        foreach ($grpo->lines as $line) {
            $lineAmount = (float) $line->qty * (float) ($line->unit_price ?? 0);
            if ($lineAmount <= 0) {
                continue;
            }

            $inventoryAccountId = $this->getInventoryAccountForLine($line);
            $inventoryByAccount[$inventoryAccountId] = round(($inventoryByAccount[$inventoryAccountId] ?? 0) + $lineAmount, 2);
            $totalAmount += $lineAmount;
        }

        if ($totalAmount <= 0) {
            throw new \Exception('GRPO total amount must be greater than zero to create journal entries.');
        }

        $liabilityAccountId = $this->getLiabilityAccount();

        $journalLines = [];
        foreach ($inventoryByAccount as $accountId => $amount) {
            $journalLines[] = [
                'account_id' => $accountId,
                'debit' => $amount,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => "Inventory receipt from GRPO {$grpo->grn_no}",
            ];
        }

        $journalLines[] = [
            'account_id' => $liabilityAccountId,
            'debit' => 0,
            'credit' => round($totalAmount, 2),
            'project_id' => null,
            'dept_id' => null,
            'memo' => "Liability for GRPO {$grpo->grn_no}",
        ];

        return new JournalDraft(
            description: "GRPO Receipt - {$grpo->grn_no}",
            lines: $journalLines,
            date: $grpo->date instanceof \Carbon\CarbonInterface
                ? $grpo->date->toDateString()
                : (string) $grpo->date,
        );
    }

    public function getInventoryAccountForLine(GoodsReceiptPOLine $line): int
    {
        if ($line->item && $line->item->category) {
            $effectiveAccount = $line->item->category->getEffectiveInventoryAccount();
            if ($effectiveAccount) {
                return $effectiveAccount->id;
            }
        }

        return $this->getInventoryAccountFromCategoryName($line->item->category->name ?? 'electronics');
    }

    protected function getInventoryAccountFromCategoryName(string $categoryName): int
    {
        $categoryName = strtolower($categoryName);
        $categoryMapping = [
            'electronics' => '1.1.3.01.02',
            'furniture' => '1.1.3.01.03',
            'stationery' => '1.1.3.01.01',
            'vehicles' => '1.1.3.01.04',
            'services' => '1.1.3.01.05',
        ];

        $accountCode = $categoryMapping[$categoryName] ?? '1.1.3.01.02';

        $defaultAccount = DB::table('accounts')
            ->where('code', $accountCode)
            ->first();

        if (! $defaultAccount) {
            throw new \Exception('No inventory account found. Please configure inventory accounts.');
        }

        return (int) $defaultAccount->id;
    }

    protected function getLiabilityAccount(): int
    {
        $apAccount = DB::table('accounts')
            ->where('code', '2.1.1.03')
            ->first();

        if (! $apAccount) {
            throw new \Exception('No AP UnInvoice account found. Please configure AP UnInvoice account.');
        }

        return (int) $apAccount->id;
    }

    public function getLiabilityAccountForTracking(): int
    {
        return $this->getLiabilityAccount();
    }
}
