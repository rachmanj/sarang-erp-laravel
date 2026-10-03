<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentJournalBuilder
{
    public function build(InventoryTransaction $transaction, ?int $counterAccountId = null): JournalDraft
    {
        $transaction->loadMissing(['item.category']);

        $item = $transaction->item;
        if (! $item) {
            throw new \RuntimeException('Inventory adjustment journal requires an inventory item.');
        }

        $inventoryAccount = $item->category?->getEffectiveAccountByType('inventory');
        if (! $inventoryAccount) {
            throw new \RuntimeException(
                "Inventory account not found for item {$item->code}. Configure category inventory account."
            );
        }

        $this->assertPostableAccount((int) $inventoryAccount->id, 'Inventory');

        $resolvedCounterAccountId = $counterAccountId ?? $this->resolveDefaultCounterAccountId();
        $this->assertPostableAccount($resolvedCounterAccountId, 'Counter (inventory adjustment)');

        $amount = round(abs((float) $transaction->total_cost), 2);
        if ($amount < 0.005) {
            throw new \RuntimeException('Inventory adjustment amount is too small to journalize.');
        }

        $quantity = (int) $transaction->quantity;
        $itemCode = $item->code ?? (string) $item->id;
        $notes = $transaction->notes ?? '';

        if ($quantity > 0) {
            $lines = [
                [
                    'account_id' => (int) $inventoryAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Stock increase - {$itemCode}",
                ],
                [
                    'account_id' => $resolvedCounterAccountId,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Stock increase offset - {$itemCode}",
                ],
            ];
        } elseif ($quantity < 0) {
            $lines = [
                [
                    'account_id' => $resolvedCounterAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Stock decrease - {$itemCode}",
                ],
                [
                    'account_id' => (int) $inventoryAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Stock decrease offset - {$itemCode}",
                ],
            ];
        } else {
            throw new \RuntimeException('Inventory adjustment quantity must not be zero.');
        }

        $description = "Penyesuaian stok {$itemCode}".($notes !== '' ? " — {$notes}" : '');

        $date = $transaction->transaction_date instanceof \Carbon\CarbonInterface
            ? $transaction->transaction_date->toDateString()
            : (string) $transaction->transaction_date;

        return new JournalDraft(
            description: $description,
            lines: $lines,
            date: $date,
        );
    }

    private function resolveDefaultCounterAccountId(): int
    {
        $account = DB::table('accounts')->where('code', '5.7')->first();

        if (! $account) {
            throw new \RuntimeException(
                'Counter account for inventory adjustment not found (code 5.7). Please configure Penyesuaian Persediaan.'
            );
        }

        return (int) $account->id;
    }

    private function assertPostableAccount(int $accountId, string $label): void
    {
        $account = DB::table('accounts')->where('id', $accountId)->first();

        if (! $account) {
            throw new \RuntimeException("{$label} account id {$accountId} not found.");
        }

        if (! (bool) $account->is_postable) {
            throw new \RuntimeException(
                "{$label} account {$account->code} ({$account->name}) is not postable."
            );
        }
    }
}
