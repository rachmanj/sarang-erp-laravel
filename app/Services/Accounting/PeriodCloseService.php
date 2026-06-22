<?php

namespace App\Services\Accounting;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodCloseService
{
    public function isDateClosed(string $date): bool
    {
        $carbon = Carbon::parse($date);
        $month = (int) $carbon->month;
        $year = (int) $carbon->year;

        $period = DB::table('periods')
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return $period ? (bool) $period->is_closed : false;
    }

    public function close(int $year, int $month): void
    {
        $exists = DB::table('periods')->where(['year' => $year, 'month' => $month])->exists();
        if ($exists) {
            DB::table('periods')->where(['year' => $year, 'month' => $month])->update([
                'is_closed' => true,
                'closed_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('periods')->insert([
                'year' => $year,
                'month' => $month,
                'is_closed' => true,
                'closed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function closeFiscalYear(int $year, ?int $postedBy = null): array
    {
        return DB::transaction(function () use ($year, $postedBy) {
            $closingJournalId = app(YearEndClosingService::class)->closeFiscalYear($year, $postedBy);

            for ($month = 1; $month <= 12; $month++) {
                $this->close($year, $month);
            }

            return [
                'closing_journal_id' => $closingJournalId,
                'year' => $year,
            ];
        });
    }

    public function openNewFiscalYear(int $year, ?int $postedBy = null): array
    {
        return DB::transaction(function () use ($year, $postedBy) {
            $rollJournalId = app(YearEndClosingService::class)->rollRetainedEarnings($year, $postedBy);

            for ($month = 1; $month <= 12; $month++) {
                $this->open($year, $month);
            }

            return [
                'roll_journal_id' => $rollJournalId,
                'year' => $year,
            ];
        });
    }

    public function open(int $year, int $month): void
    {
        DB::table('periods')->updateOrInsert(
            ['year' => $year, 'month' => $month],
            ['is_closed' => false, 'closed_at' => null, 'updated_at' => now()]
        );
    }

    public function listPeriods(int $year): array
    {
        $rows = DB::table('periods')->where('year', $year)->get()->keyBy('month');
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $rows->get($m);
            $result[] = [
                'year' => $year,
                'month' => $m,
                'is_closed' => $row ? (bool) $row->is_closed : false,
                'closed_at' => $row ? $row->closed_at : null,
            ];
        }

        return $result;
    }
}
