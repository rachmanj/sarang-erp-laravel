<?php

namespace App\Services\Bank;

use App\Models\Accounting\Account;
use App\Models\Bank\BankAccount;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatement;
use App\Models\Bank\BankStatementLine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

class BankStatementParser
{
    public function __construct(
        private BankReconciliationOpenRouterClient $client,
    ) {}

    /**
     * @return array{opening_balance: float, closing_balance: float, lines_count: int}
     */
    public function parseForReconciliation(BankReconciliation $reconciliation): array
    {
        $statement = $reconciliation->statement;
        if (! $statement || ! $statement->file_path) {
            throw new \RuntimeException('No PDF statement attached to this reconciliation.');
        }

        $absolutePath = Storage::path($statement->file_path);
        $rawText = $this->extractText($absolutePath);

        if ($rawText !== null) {
            $parsed = $this->parseWithAiFromText($rawText);
        } else {
            $parsed = $this->parseWithAiFromPdf($absolutePath, $statement->original_filename ?? 'statement.pdf');
            $rawText = '[Parsed via OpenRouter PDF: '.($statement->original_filename ?? 'statement.pdf').']';
        }

        return DB::transaction(function () use ($reconciliation, $statement, $parsed, $rawText) {
            $statement->update([
                'period_start' => $parsed['period_start'],
                'period_end' => $parsed['period_end'],
                'opening_balance' => $parsed['opening_balance'],
                'closing_balance' => $parsed['closing_balance'],
                'currency' => $parsed['currency'] ?? 'IDR',
                'raw_text' => $rawText,
                'status' => 'reconciling',
            ]);

            $reconciliation->bankLines()->delete();

            $linesCount = 0;
            $order = 0;

            foreach ($parsed['lines'] as $line) {
                $amount = round((float) ($line['amount'] ?? 0), 2);
                if ($amount <= 0) {
                    continue;
                }

                $direction = strtolower((string) ($line['direction'] ?? ''));
                if (! in_array($direction, ['debit', 'credit'], true)) {
                    continue;
                }

                $debit = $direction === 'debit' ? $amount : 0;
                $credit = $direction === 'credit' ? $amount : 0;

                BankStatementLine::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_statement_id' => $statement->id,
                    'posting_date' => $line['posting_date'],
                    'value_date' => $line['value_date'] ?? null,
                    'description' => $line['description'] ?? null,
                    'reference_no' => $line['reference_no'] ?? null,
                    'amount' => $amount,
                    'direction' => $direction,
                    'debit' => $debit,
                    'credit' => $credit,
                    'running_balance' => isset($line['running_balance']) ? round((float) $line['running_balance'], 2) : null,
                    'match_status' => BankStatementLine::MATCH_UNMATCHED,
                    'line_hash' => BankReconciliationSupport::lineHash(
                        $line['posting_date'],
                        $direction,
                        $amount,
                        $line['reference_no'] ?? null,
                        $line['description'] ?? null,
                    ),
                    'is_ai_extracted' => true,
                    'ai_confidence' => isset($line['confidence']) ? (float) $line['confidence'] : null,
                    'line_order' => ++$order,
                    'ai_meta' => $line['meta'] ?? null,
                ]);

                $linesCount++;
            }

            return [
                'opening_balance' => round((float) $parsed['opening_balance'], 2),
                'closing_balance' => round((float) $parsed['closing_balance'], 2),
                'lines_count' => $linesCount,
            ];
        });
    }

    public function importFromUpload(UploadedFile $file, ?int $bankAccountId = null): BankStatement
    {
        $storedPath = $file->store('bank-statements');

        try {
            $absolutePath = Storage::path($storedPath);
            $rawText = $this->extractText($absolutePath);

            if ($rawText !== null) {
                $parsed = $this->parseWithAiFromText($rawText);
            } else {
                $parsed = $this->parseWithAiFromPdf($absolutePath, $file->getClientOriginalName());
                $rawText = '[Parsed via OpenRouter PDF: '.$file->getClientOriginalName().']';
            }

            $resolvedBankAccountId = $bankAccountId ?? $this->resolveBankAccountId($parsed['account_number'] ?? null);
            if (! $resolvedBankAccountId) {
                $parsedNumber = $parsed['account_number'] ?? 'unknown';
                throw new \RuntimeException(
                    "No bank account registered for statement account {$parsedNumber}. "
                    .'Create one under Accounting → Bank Accounts, or select it on the import form before uploading.'
                );
            }

            return DB::transaction(function () use ($parsed, $storedPath, $rawText, $file, $resolvedBankAccountId) {
                $statement = BankStatement::create([
                    'bank_account_id' => $resolvedBankAccountId,
                    'period_start' => $parsed['period_start'],
                    'period_end' => $parsed['period_end'],
                    'opening_balance' => $parsed['opening_balance'],
                    'closing_balance' => $parsed['closing_balance'],
                    'currency' => $parsed['currency'] ?? 'IDR',
                    'original_filename' => $file->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'raw_text' => $rawText,
                    'status' => 'imported',
                    'imported_by' => Auth::id(),
                    'company_entity_id' => app(\App\Services\CompanyEntityService::class)->getDefaultEntity()->id,
                ]);

                foreach ($parsed['lines'] as $line) {
                    $amount = round((float) $line['amount'], 2);
                    if ($amount <= 0) {
                        continue;
                    }

                    $direction = strtolower((string) $line['direction']);
                    if (! in_array($direction, ['debit', 'credit'], true)) {
                        continue;
                    }

                    $hash = BankReconciliationSupport::lineHash(
                        $line['posting_date'],
                        $direction,
                        $amount,
                        $line['reference_no'] ?? null,
                        $line['description'] ?? null,
                    );

                    if (BankStatementLine::where('bank_statement_id', $statement->id)->where('line_hash', $hash)->exists()) {
                        continue;
                    }

                    BankStatementLine::create([
                        'bank_statement_id' => $statement->id,
                        'posting_date' => $line['posting_date'],
                        'value_date' => $line['value_date'] ?? null,
                        'description' => $line['description'] ?? null,
                        'reference_no' => $line['reference_no'] ?? null,
                        'amount' => $amount,
                        'direction' => $direction,
                        'running_balance' => isset($line['running_balance']) ? round((float) $line['running_balance'], 2) : null,
                        'match_status' => 'unmatched',
                        'line_hash' => $hash,
                        'ai_meta' => $line['meta'] ?? null,
                    ]);
                }

                return $statement->fresh(['lines', 'bankAccount.account']);
            });
        } catch (\Throwable $e) {
            Storage::delete($storedPath);
            throw $e;
        }
    }

    private function extractText(string $absolutePath): ?string
    {
        foreach ([false, true] as $ignoreEncryption) {
            try {
                $config = new Config;
                if ($ignoreEncryption) {
                    $config->setIgnoreEncryption(true);
                }

                $parser = new Parser([], $config);
                $text = trim($parser->parseFile($absolutePath)->getText());

                if ($text !== '') {
                    return $text;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseWithAiFromText(string $rawText): array
    {
        $model = (string) config('services.bank_reconciliation.model', 'openai/gpt-4o-mini');

        $response = $this->client->chatCompletion($model, [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $rawText],
        ]);

        return $this->decodeAiResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseWithAiFromPdf(string $absolutePath, string $filename): array
    {
        $model = (string) config('services.bank_reconciliation.model', 'openai/gpt-4o-mini');

        $response = $this->client->chatCompletionWithPdf(
            $model,
            $this->systemPrompt(),
            'Parse the attached bank statement PDF into the required JSON schema.',
            $filename,
            $absolutePath,
        );

        return $this->decodeAiResponse($response);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You parse Indonesian bank statement PDFs (Mandiri, CIMB Niaga, etc.) into normalized JSON.

Return ONLY valid JSON with this shape:
{
  "account_number": "string",
  "account_name": "string",
  "bank_name": "Mandiri|CIMB|Other",
  "currency": "IDR",
  "period_start": "YYYY-MM-DD",
  "period_end": "YYYY-MM-DD",
  "opening_balance": 0.00,
  "closing_balance": 0.00,
  "lines": [
    {
      "posting_date": "YYYY-MM-DD",
      "value_date": "YYYY-MM-DD|null",
      "description": "string",
      "reference_no": "string|null",
      "amount": 123.45,
      "direction": "debit|credit",
      "running_balance": 123.45,
      "meta": {}
    }
  ]
}

Rules:
- direction debit = money OUT of the account; credit = money IN.
- amount must always be positive.
- Normalize Indonesian and English date formats.
- Include bank fees, interest, tax lines.
- Do not invent transactions not present in the document.
- If opening/closing balances are in a summary section, use them.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function decodeAiResponse(array $response): array
    {
        $content = data_get($response, 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('AI parser returned empty response.');
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($content, true);
        if (! is_array($decoded) || empty($decoded['lines']) || ! is_array($decoded['lines'])) {
            throw new \RuntimeException('AI parser returned invalid JSON structure.');
        }

        if (empty($decoded['period_start']) || empty($decoded['period_end'])) {
            throw new \RuntimeException('AI parser could not determine statement period.');
        }

        return $decoded;
    }

    private function resolveBankAccountId(?string $accountNumber): ?int
    {
        if (! $accountNumber) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $accountNumber);
        if (! $normalized) {
            return null;
        }

        $existingId = BankAccount::query()
            ->whereRaw("REPLACE(REPLACE(account_number, '-', ''), ' ', '') = ?", [$normalized])
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $coaAccount = Account::query()
            ->where('is_postable', true)
            ->where('code', 'like', '1.1.1.%')
            ->where('name', 'like', '%'.$normalized.'%')
            ->orderBy('code')
            ->first();

        if (! $coaAccount) {
            return null;
        }

        $bankAccount = BankAccount::create([
            'code' => 'BNK-'.$normalized,
            'name' => $coaAccount->name,
            'bank_name' => $this->guessBankName($coaAccount->name),
            'account_number' => $normalized,
            'account_id' => $coaAccount->id,
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        return $bankAccount->id;
    }

    private function guessBankName(string $coaName): string
    {
        $lower = strtolower($coaName);

        if (str_contains($lower, 'mandiri')) {
            return 'Bank Mandiri';
        }

        if (str_contains($lower, 'cimb')) {
            return 'CIMB Niaga';
        }

        if (str_contains($lower, 'bca')) {
            return 'BCA';
        }

        return 'Bank';
    }
}
