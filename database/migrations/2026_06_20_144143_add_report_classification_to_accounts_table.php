<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('report_group', 50)->nullable()->after('is_postable');
            $table->enum('normal_balance', ['debit', 'credit'])->nullable()->after('report_group');
        });

        $accounts = DB::table('accounts')->get(['id', 'code', 'name', 'type']);

        foreach ($accounts as $account) {
            DB::table('accounts')->where('id', $account->id)->update([
                'report_group' => $this->inferReportGroup($account->code, $account->name, $account->type),
                'normal_balance' => $this->inferNormalBalance($account->code, $account->name, $account->type),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['report_group', 'normal_balance']);
        });
    }

    private function inferNormalBalance(string $code, string $name, string $type): string
    {
        if ($type === 'asset' && $this->isContraAsset($code, $name)) {
            return 'credit';
        }

        return match ($type) {
            'asset', 'expense' => 'debit',
            'liability', 'net_assets', 'income' => 'credit',
            default => 'debit',
        };
    }

    private function inferReportGroup(string $code, string $name, string $type): ?string
    {
        if ($type === 'asset' && $this->isContraAsset($code, $name)) {
            return 'contra_asset';
        }

        if ($type === 'income') {
            $root = $this->codeRoot($code);

            return $root === '7' ? 'other_income' : 'revenue';
        }

        if ($type === 'expense') {
            return match ($this->codeRoot($code)) {
                '5' => 'cogs',
                '6' => 'operating',
                '7' => 'other_expense',
                default => 'operating',
            };
        }

        if ($type === 'asset') {
            return 'asset';
        }

        if ($type === 'liability') {
            return 'liability';
        }

        if ($type === 'net_assets') {
            return 'equity';
        }

        return null;
    }

    private function codeRoot(string $code): string
    {
        $dot = strpos($code, '.');

        return $dot === false ? $code : substr($code, 0, $dot);
    }

    private function isContraAsset(string $code, string $name): bool
    {
        if (str_contains(strtolower($name), 'akumulasi penyusutan')) {
            return true;
        }

        return (bool) preg_match('/^1\.2\.\d+\.(03|05|07)$/', $code);
    }
};
