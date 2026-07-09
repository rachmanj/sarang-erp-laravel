<?php

namespace App\Console\Commands;

use App\Models\Bank\BankReconciliation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeBankReconciliationSessionsCommand extends Command
{
    protected $signature = 'bank-reconciliation:purge-sessions {--force : Skip confirmation}';

    protected $description = 'Delete all bank reconciliation sessions (keeps bank accounts and bank statement PDF records)';

    public function handle(): int
    {
        $counts = [
            'reconciliations' => BankReconciliation::query()->count(),
            'match_groups' => DB::table('reconciliation_match_groups')->count(),
            'book_lines' => DB::table('bank_book_lines')->count(),
            'statement_lines' => DB::table('bank_statement_lines')->whereNotNull('bank_reconciliation_id')->count(),
            'statements' => DB::table('bank_statements')->count(),
            'bank_accounts' => DB::table('bank_accounts')->count(),
        ];

        if ($counts['reconciliations'] === 0) {
            $this->info('No reconciliation sessions to purge.');

            return self::SUCCESS;
        }

        $this->table(['Entity', 'Count (will delete / affected)'], [
            ['bank_reconciliations', $counts['reconciliations']],
            ['reconciliation_match_groups', $counts['match_groups']],
            ['bank_book_lines', $counts['book_lines']],
            ['bank_statement_lines (via reconciliation)', $counts['statement_lines']],
        ]);

        $this->line('Preserved:');
        $this->line("  bank_statements: {$counts['statements']}");
        $this->line("  bank_accounts: {$counts['bank_accounts']}");
        $this->line('  PDF files on disk (not deleted)');

        if (! $this->option('force') && ! $this->confirm('Delete all reconciliation sessions?', false)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            BankReconciliation::query()->delete();
        });

        $this->info("Purged {$counts['reconciliations']} reconciliation session(s).");
        $this->comment('Orphan bank_statements may remain without lines; create new sessions from the Koran grid.');

        return self::SUCCESS;
    }
}
