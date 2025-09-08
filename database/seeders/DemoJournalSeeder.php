<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Services\Accounting\PostingService;
use Illuminate\Support\Carbon;

class DemoJournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = app(PostingService::class);
        $today = now()->toDateString();

        // Simple AR sale: Dr AR 1.000.000, Cr Revenue 1.000.000
        $service->postJournal([
            'date' => $today,
            'description' => 'Demo AR sale',
            'source_type' => 'demo',
            'source_id' => 1,
            'lines' => [
                ['account_id' => $this->accountId('1.1.4'), 'debit' => 1000000, 'credit' => 0, 'memo' => 'AR'],
                ['account_id' => $this->accountId('4.1.1'), 'debit' => 0, 'credit' => 1000000, 'memo' => 'Revenue'],
            ],
        ]);

        // Cash receipt from AR: Dr Cash 1.000.000, Cr AR 1.000.000
        $service->postJournal([
            'date' => $today,
            'description' => 'Demo cash receipt',
            'source_type' => 'demo',
            'source_id' => 2,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => 1000000, 'credit' => 0, 'memo' => 'Bank'],
                ['account_id' => $this->accountId('1.1.4'), 'debit' => 0, 'credit' => 1000000, 'memo' => 'AR'],
            ],
        ]);
    }

    private function accountId(string $code): int
    {
        $id = \DB::table('accounts')->where('code', $code)->value('id');
        if (!$id) {
            throw new \RuntimeException("Account code {$code} not found. Seed CoA first.");
        }
        return (int)$id;
    }
}
