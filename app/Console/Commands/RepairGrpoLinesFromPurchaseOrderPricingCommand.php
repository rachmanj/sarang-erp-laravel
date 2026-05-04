<?php

namespace App\Console\Commands;

use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use App\Services\GrpoLinePurchaseOrderPricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairGrpoLinesFromPurchaseOrderPricingCommand extends Command
{
    protected $signature = 'grpo:repair-lines-from-po-pricing
                            {--dry-run : Show proposed changes without saving}
                            {--grpo= : Process only this goods_receipt_po id (primary key)}';

    protected $description = 'Rebuild GRPO line unit_price, amount, account_id, and tax_code_id from the linked purchase order. Refresh GRPO total_amount from line sums. Does not repost journals or inventory.';

    public function handle(GrpoLinePurchaseOrderPricingService $pricingService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $filterGrpoId = $this->option('grpo');

        $query = GoodsReceiptPO::query()
            ->whereNotNull('purchase_order_id')
            ->with('lines')
            ->orderBy('id');

        if ($filterGrpoId !== null && $filterGrpoId !== '') {
            $query->whereKey((int) $filterGrpoId);
        }

        $grpos = $query->get();

        if ($grpos->isEmpty()) {
            $this->info('No GRPOs with purchase_order_id set found (for the given filter).');

            return self::SUCCESS;
        }

        $suffix = $dryRun ? ' (dry run)' : '';
        $this->info(sprintf('Processing %d GRPO(s)%s.', $grpos->count(), $suffix));

        $linesApplied = 0;
        $linesProposedDryRun = 0;
        $linesSkippedNoPoLine = 0;
        $linesUnchanged = 0;

        foreach ($grpos as $grpo) {
            $poId = (int) $grpo->purchase_order_id;
            if ($poId <= 0) {
                continue;
            }

            $indexed = $pricingService->mapFirstLinesKeyedByOrderAndInventoryItem([$poId]);

            if ($dryRun) {
                foreach ($grpo->lines as $line) {
                    $itemId = (int) $line->item_id;
                    $poLine = $indexed[$poId.'|'.$itemId] ?? null;
                    if ($poLine === null) {
                        $this->warn("Skip line #{$line->id} (GRPO #{$grpo->id}, GRN {$grpo->grn_no}): no PO #{$poId} line for item {$itemId}.");
                        $linesSkippedNoPoLine++;

                        continue;
                    }

                    $unitPrice = $poLine->effectivePurchasingUnitPrice();
                    $qty = (float) $line->qty;
                    $amount = round($unitPrice * $qty, 2);
                    $accountId = (int) $poLine->account_id;
                    $taxCodeId = $poLine->tax_code_id;

                    $currentUnitPrice = round((float) $line->unit_price, 2);
                    $currentAmount = round((float) $line->amount, 2);
                    $targetUnitPrice = round($unitPrice, 2);

                    $changed = abs($currentUnitPrice - $targetUnitPrice) > 0.0005
                        || abs($currentAmount - $amount) > 0.005
                        || (int) $line->account_id !== $accountId
                        || ($line->tax_code_id !== $taxCodeId);

                    if (! $changed) {
                        $linesUnchanged++;

                        continue;
                    }

                    $this->line(sprintf(
                        '[dry-run] GRPO #%d GRN %s line #%d item %s: unit %s→%s, amount %s→%s, account %s→%s, tax_code_id %s→%s',
                        $grpo->id,
                        $grpo->grn_no ?? '-',
                        $line->id,
                        (string) $itemId,
                        (string) $currentUnitPrice,
                        (string) $targetUnitPrice,
                        (string) $currentAmount,
                        (string) $amount,
                        (string) $line->account_id,
                        (string) $accountId,
                        json_encode($line->tax_code_id),
                        json_encode($taxCodeId)
                    ));
                    $linesProposedDryRun++;
                }

                continue;
            }

            DB::transaction(function () use ($grpo, $indexed, $poId, &$linesApplied, &$linesSkippedNoPoLine, &$linesUnchanged): void {
                foreach ($grpo->lines as $line) {
                    $itemId = (int) $line->item_id;
                    $poLine = $indexed[$poId.'|'.$itemId] ?? null;
                    if ($poLine === null) {
                        $linesSkippedNoPoLine++;

                        continue;
                    }

                    $unitPrice = $poLine->effectivePurchasingUnitPrice();
                    $qty = (float) $line->qty;
                    $amount = round($unitPrice * $qty, 2);
                    $accountId = (int) $poLine->account_id;
                    $taxCodeId = $poLine->tax_code_id;

                    $currentUnitPrice = round((float) $line->unit_price, 2);
                    $currentAmount = round((float) $line->amount, 2);
                    $targetUnitPrice = round($unitPrice, 2);

                    $changed = abs($currentUnitPrice - $targetUnitPrice) > 0.0005
                        || abs($currentAmount - $amount) > 0.005
                        || (int) $line->account_id !== $accountId
                        || ($line->tax_code_id !== $taxCodeId);

                    if (! $changed) {
                        $linesUnchanged++;

                        continue;
                    }

                    $line->forceFill([
                        'unit_price' => $targetUnitPrice,
                        'amount' => $amount,
                        'account_id' => $accountId,
                        'tax_code_id' => $taxCodeId,
                    ])->save();
                    $linesApplied++;
                }

                $newTotal = (float) GoodsReceiptPOLine::query()
                    ->where('grpo_id', $grpo->id)
                    ->sum('amount');

                $grpo->forceFill(['total_amount' => $newTotal])->save();
            });

            $this->line(sprintf(
                'GRPO #%s (%s) total_amount synced from lines.',
                (string) $grpo->id,
                $grpo->grn_no ?? '-'
            ));
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Proposed line updates (not saved): {$linesProposedDryRun}");
        } else {
            $this->info("Applied line updates: {$linesApplied}");
            $this->info('Synced GRPO header total_amount from line sums.');
        }

        $this->info('Lines skipped (item not found on PO): '.$linesSkippedNoPoLine);
        $this->info('Lines already matching PO: '.$linesUnchanged);
        $this->warn('Journals and inventory transactions for these GRPOs were not modified. Reconcile if required.');

        return self::SUCCESS;
    }
}
