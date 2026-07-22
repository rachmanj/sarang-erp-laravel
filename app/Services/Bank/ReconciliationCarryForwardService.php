<?php

namespace App\Services\Bank;

use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;
use Illuminate\Support\Facades\DB;

class ReconciliationCarryForwardService
{
    /**
     * Import outstanding lines from the previous completed (or latest prior) session
     * into the new session so timing differences can clear across periods.
     */
    public function importOutstandingInto(BankReconciliation $reconciliation): int
    {
        $prior = BankReconciliation::query()
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->where('id', '!=', $reconciliation->id)
            ->whereDate('periode', '<', $reconciliation->periode->toDateString())
            ->orderByDesc('periode')
            ->first();

        if (! $prior) {
            return 0;
        }

        return DB::transaction(function () use ($reconciliation, $prior) {
            $imported = 0;

            $outstandingBook = BankBookLine::query()
                ->where('bank_reconciliation_id', $prior->id)
                ->where('match_status', BankBookLine::MATCH_OUTSTANDING)
                ->get();

            foreach ($outstandingBook as $source) {
                $already = BankBookLine::query()
                    ->where('bank_reconciliation_id', $reconciliation->id)
                    ->where(function ($q) use ($source) {
                        $q->where('carried_from_book_line_id', $source->id);
                        if ($source->journal_line_id) {
                            $q->orWhere('journal_line_id', $source->journal_line_id);
                        }
                    })
                    ->exists();

                if ($already) {
                    continue;
                }

                BankBookLine::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'journal_line_id' => $source->journal_line_id,
                    'doc_date' => $source->doc_date,
                    'posting_date' => $source->posting_date,
                    'doc_num' => $source->doc_num,
                    'ref_doc_num' => $source->ref_doc_num,
                    'transaction_id' => $source->transaction_id,
                    'description' => $source->description,
                    'project_code' => $source->project_code,
                    'debit' => $source->debit,
                    'credit' => $source->credit,
                    'debit_foreign' => $source->debit_foreign,
                    'credit_foreign' => $source->credit_foreign,
                    'currency_code' => $source->currency_code,
                    'match_status' => BankBookLine::MATCH_UNMATCHED,
                    'line_notes' => 'Carried forward from '.$prior->periode->format('M Y'),
                    'is_carried_forward' => true,
                    'carried_from_book_line_id' => $source->id,
                    'origin_reconciliation_id' => $source->origin_reconciliation_id ?? $prior->id,
                ]);

                $imported++;
            }

            $outstandingBank = BankStatementLine::query()
                ->where('bank_reconciliation_id', $prior->id)
                ->where('match_status', BankStatementLine::MATCH_OUTSTANDING)
                ->get();

            foreach ($outstandingBank as $source) {
                $already = BankStatementLine::query()
                    ->where('bank_reconciliation_id', $reconciliation->id)
                    ->where('carried_from_bank_line_id', $source->id)
                    ->exists();

                if ($already) {
                    continue;
                }

                BankStatementLine::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_statement_id' => $reconciliation->bank_statement_id,
                    'posting_date' => $source->posting_date,
                    'value_date' => $source->value_date,
                    'description' => $source->description,
                    'reference_no' => $source->reference_no,
                    'amount' => $source->amount,
                    'direction' => $source->direction,
                    'debit' => $source->debit,
                    'credit' => $source->credit,
                    'running_balance' => null,
                    'match_status' => BankStatementLine::MATCH_UNMATCHED,
                    'line_notes' => 'Carried forward from '.$prior->periode->format('M Y'),
                    'line_order' => ($reconciliation->bankLines()->max('line_order') ?? 0) + 1,
                    'is_ai_extracted' => false,
                    'line_hash' => BankReconciliationSupport::lineHash(
                        $source->posting_date->toDateString(),
                        $source->direction,
                        (float) $source->amount,
                        $source->reference_no,
                        'cf-'.$source->id.'-'.$source->description,
                    ),
                    'is_carried_forward' => true,
                    'carried_from_bank_line_id' => $source->id,
                    'origin_reconciliation_id' => $source->origin_reconciliation_id ?? $prior->id,
                ]);

                $imported++;
            }

            return $imported;
        });
    }
}
