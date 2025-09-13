<?php

namespace App\Services\Accounting;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationEntry;
use App\Models\AssetDepreciationRun;
use App\Models\AssetDisposal;
use App\Models\Accounting\Account;
use App\Services\Accounting\PostingService;
use App\Services\Accounting\PeriodCloseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FixedAssetService
{
    public function __construct(
        private PostingService $postingService,
        private PeriodCloseService $periodCloseService
    ) {}

    /**
     * Create a depreciation run for a specific period
     */
    public function createDepreciationRun(string $period, int $createdBy): AssetDepreciationRun
    {
        // Validate period format (YYYY-MM)
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw new \InvalidArgumentException('Period must be in YYYY-MM format');
        }

        // Check if period is closed
        $periodDate = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        if ($this->periodCloseService->isDateClosed($periodDate)) {
            throw new \InvalidArgumentException('Cannot create depreciation run for closed period: ' . $period);
        }

        // Check if run already exists for this period
        if (AssetDepreciationRun::where('period', $period)->exists()) {
            throw new \InvalidArgumentException('Depreciation run already exists for period: ' . $period);
        }

        return AssetDepreciationRun::create([
            'period' => $period,
            'status' => 'draft',
            'total_depreciation' => 0,
            'asset_count' => 0,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Calculate depreciation entries for all active assets in a period
     */
    public function calculateDepreciationEntries(AssetDepreciationRun $run): array
    {
        $period = $run->period;
        $periodDate = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        // Get all active, depreciable assets
        $assets = Asset::active()
            ->depreciable()
            ->where('placed_in_service_date', '<=', $periodDate->endOfMonth())
            ->whereDoesntHave('depreciationEntries', function ($query) use ($period) {
                $query->where('period', $period)->where('book', 'financial');
            })
            ->with(['category', 'fund', 'project', 'department'])
            ->get();

        $entries = [];
        $totalDepreciation = 0;

        foreach ($assets as $asset) {
            $depreciationAmount = $this->calculateAssetDepreciation($asset, $period);

            if ($depreciationAmount > 0) {
                $entry = [
                    'asset_id' => $asset->id,
                    'period' => $period,
                    'amount' => $depreciationAmount,
                    'book' => 'financial',
                    'fund_id' => $asset->fund_id,
                    'project_id' => $asset->project_id,
                    'department_id' => $asset->department_id,
                ];

                $entries[] = $entry;
                $totalDepreciation += $depreciationAmount;
            }
        }

        // Update run totals
        $run->update([
            'total_depreciation' => $totalDepreciation,
            'asset_count' => count($entries),
        ]);

        return $entries;
    }

    /**
     * Calculate monthly depreciation for a specific asset and period
     */
    public function calculateAssetDepreciation(Asset $asset, string $period): float
    {
        if ($asset->category->non_depreciable) {
            return 0;
        }

        $periodDate = Carbon::createFromFormat('Y-m', $period);
        $serviceDate = Carbon::parse($asset->placed_in_service_date);

        // Check if asset was in service during this period
        if ($serviceDate->gt($periodDate->endOfMonth())) {
            return 0;
        }

        // Calculate months in service up to this period
        $monthsInService = $serviceDate->diffInMonths($periodDate->endOfMonth()) + 1;

        // Check if fully depreciated
        if ($monthsInService >= $asset->life_months) {
            // Calculate remaining depreciation
            $totalDepreciationToDate = $asset->getTotalDepreciationToDate();
            $depreciableCost = $asset->depreciable_cost;

            if ($totalDepreciationToDate >= $depreciableCost) {
                return 0;
            }

            return $depreciableCost - $totalDepreciationToDate;
        }

        // Calculate monthly depreciation
        $monthlyDepreciation = $asset->calculateMonthlyDepreciation();

        // First month proration (if needed)
        if ($serviceDate->day > 1) {
            $daysInMonth = $serviceDate->daysInMonth;
            $daysRemaining = $daysInMonth - $serviceDate->day + 1;
            $prorationFactor = $daysRemaining / $daysInMonth;
            $monthlyDepreciation = $monthlyDepreciation * $prorationFactor;
        }

        return round($monthlyDepreciation, 2);
    }

    /**
     * Create depreciation entries and save them as draft
     */
    public function createDraftDepreciationEntries(AssetDepreciationRun $run): void
    {
        $entries = $this->calculateDepreciationEntries($run);

        foreach ($entries as $entryData) {
            AssetDepreciationEntry::create($entryData);
        }
    }

    /**
     * Post depreciation run to GL
     * 
     * @param AssetDepreciationRun $run
     * @param int $postedBy
     * @return void
     */
    public function postDepreciationRun(AssetDepreciationRun $run, int $postedBy): void
    {
        if (!$run->canBePosted()) {
            throw new \InvalidArgumentException('Depreciation run cannot be posted');
        }

        $entries = AssetDepreciationEntry::where('period', $run->period)
            ->where('book', 'financial')
            ->whereNull('journal_id')
            ->with(['asset.category'])
            ->get();

        if ($entries->isEmpty()) {
            throw new \InvalidArgumentException('No depreciation entries found for this run');
        }

        // Group entries by category and dimensions for GL posting
        $groupedEntries = $this->groupEntriesForPosting($entries);

        $journalLines = [];
        $totalDepreciation = 0;

        foreach ($groupedEntries as $group) {
            $category = $group['category'];
            $totalAmount = $group['total_amount'];

            // Dr Depreciation Expense
            $journalLines[] = [
                'account_id' => $category->depreciation_expense_account_id,
                'debit' => $totalAmount,
                'credit' => 0,
                'fund_id' => $group['fund_id'],
                'project_id' => $group['project_id'],
                'department_id' => $group['department_id'],
                'memo' => "Depreciation for {$category->name} - {$run->period_display}",
            ];

            // Cr Accumulated Depreciation
            $journalLines[] = [
                'account_id' => $category->accumulated_depreciation_account_id,
                'debit' => 0,
                'credit' => $totalAmount,
                'fund_id' => $group['fund_id'],
                'project_id' => $group['project_id'],
                'department_id' => $group['department_id'],
                'memo' => "Accumulated depreciation for {$category->name} - {$run->period_display}",
            ];

            $totalDepreciation += $totalAmount;
        }

        // Post journal
        $journal = $this->postingService->postJournal([
            'date' => Carbon::createFromFormat('Y-m', $run->period)->endOfMonth()->toDateString(),
            'description' => "Monthly Depreciation - {$run->period_display}",
            'source_type' => AssetDepreciationRun::class,
            'source_id' => $run->id,
            'lines' => $journalLines,
        ]);

        // Update depreciation entries with journal reference
        AssetDepreciationEntry::where('period', $run->period)
            ->where('book', 'financial')
            ->whereNull('journal_id')
            ->update(['journal_id' => $journal->id]);

        // Update run status
        $run->update([
            'status' => 'posted',
            'journal_id' => $journal->id,
            'posted_by' => $postedBy,
            'posted_at' => now(),
        ]);

        // Update asset accumulated depreciation
        $this->updateAssetDepreciation($entries);
    }

    /**
     * Group depreciation entries by category and dimensions for GL posting
     */
    private function groupEntriesForPosting($entries): array
    {
        $grouped = [];

        foreach ($entries as $entry) {
            $category = $entry->asset->category;
            $key = $category->id . '_' . $entry->fund_id . '_' . $entry->project_id . '_' . $entry->department_id;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'category' => $category,
                    'fund_id' => $entry->fund_id,
                    'project_id' => $entry->project_id,
                    'department_id' => $entry->department_id,
                    'total_amount' => 0,
                ];
            }

            $grouped[$key]['total_amount'] += $entry->amount;
        }

        return $grouped;
    }

    /**
     * Update accumulated depreciation on assets
     */
    private function updateAssetDepreciation($entries): void
    {
        $assetDepreciation = [];

        foreach ($entries as $entry) {
            if (!isset($assetDepreciation[$entry->asset_id])) {
                $assetDepreciation[$entry->asset_id] = 0;
            }
            $assetDepreciation[$entry->asset_id] += $entry->amount;
        }

        foreach ($assetDepreciation as $assetId => $amount) {
            Asset::where('id', $assetId)->increment('accumulated_depreciation', $amount);
        }
    }

    /**
     * Reverse a posted depreciation run
     */
    public function reverseDepreciationRun(AssetDepreciationRun $run, int $reversedBy): void
    {
        if (!$run->canBeReversed()) {
            throw new \InvalidArgumentException('Depreciation run cannot be reversed');
        }

        DB::transaction(function () use ($run, $reversedBy) {
            // Reverse the journal
            $this->postingService->reverseJournal(
                $run->journal_id,
                Carbon::createFromFormat('Y-m', $run->period)->endOfMonth()->toDateString(),
                $reversedBy
            );

            // Update depreciation entries
            AssetDepreciationEntry::where('period', $run->period)
                ->where('book', 'financial')
                ->where('journal_id', $run->journal_id)
                ->update(['journal_id' => null]);

            // Update run status
            $run->update([
                'status' => 'reversed',
                'journal_id' => null,
                'posted_by' => null,
                'posted_at' => null,
            ]);

            // Reverse asset accumulated depreciation
            $entries = AssetDepreciationEntry::where('period', $run->period)
                ->where('book', 'financial')
                ->get();

            $assetDepreciation = [];
            foreach ($entries as $entry) {
                if (!isset($assetDepreciation[$entry->asset_id])) {
                    $assetDepreciation[$entry->asset_id] = 0;
                }
                $assetDepreciation[$entry->asset_id] += $entry->amount;
            }

            foreach ($assetDepreciation as $assetId => $amount) {
                Asset::where('id', $assetId)->decrement('accumulated_depreciation', $amount);
            }
        });
    }

    /**
     * Get depreciation schedule for an asset
     */
    public function getAssetDepreciationSchedule(Asset $asset, int $months = 12): array
    {
        $schedule = [];
        $serviceDate = Carbon::parse($asset->placed_in_service_date);
        $currentDate = now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $periodDate = $currentDate->copy()->addMonths($i);
            $period = $periodDate->format('Y-m');

            $depreciation = $this->calculateAssetDepreciation($asset, $period);

            if ($depreciation > 0) {
                $schedule[] = [
                    'period' => $period,
                    'period_display' => $periodDate->format('F Y'),
                    'amount' => $depreciation,
                    'cumulative' => $asset->accumulated_depreciation + array_sum(array_column($schedule, 'amount')) + $depreciation,
                ];
            }
        }

        return $schedule;
    }

    /**
     * Post asset disposal to GL
     */
    public function postAssetDisposal(AssetDisposal $disposal, int $postedBy): void
    {
        if (!$disposal->canBePosted()) {
            throw new \InvalidArgumentException('Asset disposal cannot be posted');
        }

        $asset = $disposal->asset;
        $category = $asset->category;

        // Check if period is closed
        if ($this->periodCloseService->isDateClosed($disposal->disposal_date)) {
            throw new \InvalidArgumentException('Cannot post disposal for closed period: ' . $disposal->disposal_date->format('Y-m'));
        }

        DB::transaction(function () use ($disposal, $asset, $category, $postedBy) {
            $journalLines = [];

            // Remove asset from books
            // Dr Accumulated Depreciation
            $journalLines[] = [
                'account_id' => $category->accumulated_depreciation_account_id,
                'debit' => $asset->accumulated_depreciation,
                'credit' => 0,
                'fund_id' => $asset->fund_id,
                'project_id' => $asset->project_id,
                'department_id' => $asset->department_id,
                'memo' => "Remove accumulated depreciation for {$asset->name}",
            ];

            // Cr Fixed Asset
            $journalLines[] = [
                'account_id' => $category->asset_account_id,
                'debit' => 0,
                'credit' => $asset->acquisition_cost,
                'fund_id' => $asset->fund_id,
                'project_id' => $asset->project_id,
                'department_id' => $asset->department_id,
                'memo' => "Remove fixed asset {$asset->name}",
            ];

            // Handle disposal proceeds
            if ($disposal->disposal_proceeds > 0) {
                // Dr Cash/Bank (assuming cash for now - could be configurable)
                $journalLines[] = [
                    'account_id' => 1, // Assuming cash account - should be configurable
                    'debit' => $disposal->disposal_proceeds,
                    'credit' => 0,
                    'fund_id' => $asset->fund_id,
                    'project_id' => $asset->project_id,
                    'department_id' => $asset->department_id,
                    'memo' => "Proceeds from disposal of {$asset->name}",
                ];
            }

            // Handle gain/loss
            if ($disposal->gain_loss_type === 'gain') {
                // Cr Gain on Disposal
                $journalLines[] = [
                    'account_id' => $category->gain_on_disposal_account_id,
                    'debit' => 0,
                    'credit' => $disposal->gain_loss_amount,
                    'fund_id' => $asset->fund_id,
                    'project_id' => $asset->project_id,
                    'department_id' => $asset->department_id,
                    'memo' => "Gain on disposal of {$asset->name}",
                ];
            } elseif ($disposal->gain_loss_type === 'loss') {
                // Dr Loss on Disposal
                $journalLines[] = [
                    'account_id' => $category->loss_on_disposal_account_id,
                    'debit' => $disposal->gain_loss_amount,
                    'credit' => 0,
                    'fund_id' => $asset->fund_id,
                    'project_id' => $asset->project_id,
                    'department_id' => $asset->department_id,
                    'memo' => "Loss on disposal of {$asset->name}",
                ];
            }

            // Post journal
            $journal = $this->postingService->postJournal([
                'date' => $disposal->disposal_date->toDateString(),
                'description' => "Asset Disposal - {$asset->name} ({$disposal->disposal_type_display})",
                'source_type' => AssetDisposal::class,
                'source_id' => $disposal->id,
                'lines' => $journalLines,
            ]);

            // Update disposal status
            $disposal->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ]);
        });
    }

    /**
     * Reverse a posted asset disposal
     */
    public function reverseAssetDisposal(AssetDisposal $disposal, int $reversedBy): void
    {
        if (!$disposal->canBeReversed()) {
            throw new \InvalidArgumentException('Asset disposal cannot be reversed');
        }

        DB::transaction(function () use ($disposal, $reversedBy) {
            // Reverse the journal
            $this->postingService->reverseJournal(
                $disposal->journal_id,
                $disposal->disposal_date->toDateString(),
                $reversedBy
            );

            // Update disposal status
            $disposal->update([
                'status' => 'reversed',
                'journal_id' => null,
                'posted_by' => null,
                'posted_at' => null,
            ]);

            // Restore asset status
            $disposal->asset->update([
                'status' => 'active',
                'disposal_date' => null,
            ]);
        });
    }
}
