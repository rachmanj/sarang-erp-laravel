<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PostingService
{
    public function postJournal(array $payload): int
    {
        // Expected $payload keys: date, description, period_id|null, source_type, source_id, posted_by|null, lines[]
        // Each line: account_id, debit, credit, project_id|null, fund_id|null, dept_id|null, memo|null
        $this->validatePayload($payload);
        $this->assertBalanced($payload['lines']);

        return DB::transaction(function () use ($payload) {
            $journalId = DB::table('journals')->insertGetId([
                'date' => $payload['date'],
                'description' => $payload['description'] ?? null,
                'period_id' => $payload['period_id'] ?? null,
                'source_type' => $payload['source_type'],
                'source_id' => $payload['source_id'],
                'posted_by' => $payload['posted_by'] ?? null,
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Generate journal number: JNL-YYYYMM-###### using journal id and date
            $dateYm = date('Ym', strtotime($payload['date']));
            $journalNo = sprintf('JNL-%s-%06d', $dateYm, $journalId);
            DB::table('journals')->where('id', $journalId)->update(['journal_no' => $journalNo]);

            $linesInsert = [];
            foreach ($payload['lines'] as $l) {
                $linesInsert[] = [
                    'journal_id' => $journalId,
                    'account_id' => $l['account_id'],
                    'debit' => (float)($l['debit'] ?? 0),
                    'credit' => (float)($l['credit'] ?? 0),
                    'project_id' => empty($l['project_id']) ? null : $l['project_id'],
                    'fund_id' => empty($l['fund_id']) ? null : $l['fund_id'],
                    'dept_id' => empty($l['dept_id']) ? null : $l['dept_id'],
                    'memo' => $l['memo'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('journal_lines')->insert($linesInsert);

            return $journalId;
        });
    }

    public function reverseJournal(int $journalId, ?string $date = null, ?int $postedBy = null): int
    {
        $date = $date ?: now()->toDateString();

        $journal = DB::table('journals')->where('id', $journalId)->first();
        if (!$journal) {
            throw new \InvalidArgumentException('Journal not found');
        }

        $lines = DB::table('journal_lines')->where('journal_id', $journalId)->get();
        if ($lines->isEmpty()) {
            throw new \RuntimeException('Cannot reverse empty journal');
        }

        $payload = [
            'date' => $date,
            'description' => 'Reversal of #' . $journalId . ($journal->description ? ' - ' . $journal->description : ''),
            'period_id' => $journal->period_id,
            'source_type' => $journal->source_type,
            'source_id' => $journal->source_id,
            'posted_by' => $postedBy,
            'lines' => [],
        ];

        foreach ($lines as $l) {
            $payload['lines'][] = [
                'account_id' => $l->account_id,
                'debit' => (float)$l->credit,
                'credit' => (float)$l->debit,
                'project_id' => $l->project_id,
                'fund_id' => $l->fund_id,
                'dept_id' => $l->dept_id,
                'memo' => 'Reversal of line ' . $l->id,
            ];
        }

        return $this->postJournal($payload);
    }

    private function validatePayload(array $payload): void
    {
        foreach (['date', 'source_type', 'source_id', 'lines'] as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new \InvalidArgumentException("Missing required key: {$key}");
            }
        }
        if (!is_array($payload['lines']) || count($payload['lines']) === 0) {
            throw new \InvalidArgumentException('Journal must contain at least one line');
        }
        foreach ($payload['lines'] as $idx => $l) {
            if (empty($l['account_id'])) {
                throw new \InvalidArgumentException("Line {$idx} missing account_id");
            }
            $debit = (float)($l['debit'] ?? 0);
            $credit = (float)($l['credit'] ?? 0);
            if ($debit < 0 || $credit < 0) {
                throw new \InvalidArgumentException("Line {$idx} has negative amount");
            }
            if ($debit === 0.0 && $credit === 0.0) {
                throw new \InvalidArgumentException("Line {$idx} must have debit or credit");
            }
        }
    }

    private function assertBalanced(array $lines): void
    {
        $sumDebit = 0.0;
        $sumCredit = 0.0;
        foreach ($lines as $l) {
            $sumDebit += (float)($l['debit'] ?? 0);
            $sumCredit += (float)($l['credit'] ?? 0);
        }
        if (round($sumDebit - $sumCredit, 2) !== 0.0) {
            throw new \InvalidArgumentException('Journal is not balanced');
        }
    }
}
