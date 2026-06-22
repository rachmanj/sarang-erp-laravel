<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_accounts') || ! Schema::hasTable('accounts')) {
            return;
        }

        $coaAccounts = DB::table('accounts')
            ->where('is_postable', true)
            ->where('code', 'like', '1.1.1.%')
            ->where('code', '!=', '1.1.1.01')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        foreach ($coaAccounts as $coaAccount) {
            if (! preg_match('/(\d{8,})/', (string) $coaAccount->name, $matches)) {
                continue;
            }

            $accountNumber = $matches[1];

            $exists = DB::table('bank_accounts')
                ->whereRaw("REPLACE(REPLACE(account_number, '-', ''), ' ', '') = ?", [$accountNumber])
                ->exists();

            if ($exists) {
                continue;
            }

            $bankName = match (true) {
                str_contains(strtolower((string) $coaAccount->name), 'mandiri') => 'Bank Mandiri',
                str_contains(strtolower((string) $coaAccount->name), 'cimb') => 'CIMB Niaga',
                str_contains(strtolower((string) $coaAccount->name), 'bca') => 'BCA',
                default => 'Bank',
            };

            DB::table('bank_accounts')->insert([
                'account_id' => $coaAccount->id,
                'code' => 'BNK-'.$accountNumber,
                'name' => $coaAccount->name,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'currency' => 'IDR',
                'is_restricted' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            return;
        }

        DB::table('bank_accounts')->where('code', 'like', 'BNK-%')->delete();
    }
};
