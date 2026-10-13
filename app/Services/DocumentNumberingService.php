<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentNumberingService
{
    private const LEGACY_DOCUMENT_TYPES = [
        'purchase_order' => 'PO',
        'sales_order' => 'SO',
        'purchase_invoice' => 'PINV',
        'sales_invoice' => 'SINV',
        'purchase_payment' => 'PP',
        'sales_receipt' => 'SR',
        'asset_disposal' => 'DIS',
        'goods_receipt' => 'GR',
        'grpo' => 'GRPO',
        'cash_expense' => 'CEV',
        'journal' => 'JNL',
        'account_statement' => 'AST',
    ];

    private const ENTITY_DOCUMENT_CODES = [
        'purchase_order' => '01',
        'goods_receipt' => '02',
        'grpo' => '02',
        'purchase_invoice' => '03',
        'purchase_payment' => '04',
        'sales_quotation' => '05',
        'sales_order' => '06',
        'delivery_order' => '07',
        'sales_invoice' => '08',
        'sales_credit_memo' => '14',
        'sales_receipt' => '09',
        'asset_disposal' => '10',
        'cash_expense' => '11',
        'journal' => '12',
        'account_statement' => '13',
    ];

    /**
     * @var array<string, array{table: string, column: string, entity_column: string|null}>
     */
    private const ENTITY_DOCUMENT_NUMBER_SOURCES = [
        'purchase_order' => ['table' => 'purchase_orders', 'column' => 'order_no', 'entity_column' => 'company_entity_id'],
        'goods_receipt' => ['table' => 'goods_receipt_po', 'column' => 'grn_no', 'entity_column' => 'company_entity_id'],
        'grpo' => ['table' => 'goods_receipt_po', 'column' => 'grn_no', 'entity_column' => 'company_entity_id'],
        'purchase_invoice' => ['table' => 'purchase_invoices', 'column' => 'invoice_no', 'entity_column' => 'company_entity_id'],
        'purchase_payment' => ['table' => 'purchase_payments', 'column' => 'payment_no', 'entity_column' => 'company_entity_id'],
        'sales_quotation' => ['table' => 'sales_quotations', 'column' => 'quotation_no', 'entity_column' => 'company_entity_id'],
        'sales_order' => ['table' => 'sales_orders', 'column' => 'order_no', 'entity_column' => 'company_entity_id'],
        'delivery_order' => ['table' => 'delivery_orders', 'column' => 'do_number', 'entity_column' => 'company_entity_id'],
        'sales_invoice' => ['table' => 'sales_invoices', 'column' => 'invoice_no', 'entity_column' => 'company_entity_id'],
        'sales_credit_memo' => ['table' => 'sales_credit_memos', 'column' => 'memo_no', 'entity_column' => 'company_entity_id'],
        'sales_receipt' => ['table' => 'sales_receipts', 'column' => 'receipt_no', 'entity_column' => 'company_entity_id'],
        'asset_disposal' => ['table' => 'asset_disposals', 'column' => 'disposal_no', 'entity_column' => 'company_entity_id'],
        'cash_expense' => ['table' => 'cash_expenses', 'column' => 'expense_no', 'entity_column' => 'company_entity_id'],
        'journal' => ['table' => 'journals', 'column' => 'journal_no', 'entity_column' => 'company_entity_id'],
        'account_statement' => ['table' => 'account_statements', 'column' => 'statement_no', 'entity_column' => 'company_entity_id'],
    ];

    /** @var array<string, array{table: string, column: string, entity_column: string|null}|null> */
    private static array $verifiedEntityDocumentNumberSources = [];

    public function __construct(
        private CompanyEntityService $companyEntityService
    ) {}

    /**
     * Generate a document number for the given document type and date.
     */
    public function generateNumber(string $documentType, string $date, array $options = []): string
    {
        if ($this->usesEntityFormat($documentType)) {
            $entity = $this->companyEntityService->getEntity($options['company_entity_id'] ?? null);
            $year = Carbon::parse($date)->year;
            $docCode = self::ENTITY_DOCUMENT_CODES[$documentType];
            $sequence = $this->getNextEntitySequence($entity->id, $entity->code, $documentType, $docCode, $year);

            return $this->formatEntityNumber($entity->code, $year, $docCode, $sequence);
        }

        if (! isset(self::LEGACY_DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        $prefix = self::LEGACY_DOCUMENT_TYPES[$documentType];
        $yearMonth = Carbon::parse($date)->format('Ym');
        $sequence = $this->getNextLegacySequence($documentType, $yearMonth);

        return sprintf('%s-%s-%06d', $prefix, $yearMonth, $sequence);
    }

    /**
     * Preview the next document number without consuming/incrementing the sequence.
     */
    public function previewNumber(string $documentType, string $date, array $options = []): string
    {
        if ($this->usesEntityFormat($documentType)) {
            $entity = $this->companyEntityService->getEntity($options['company_entity_id'] ?? null);
            $year = Carbon::parse($date)->year;
            $docCode = self::ENTITY_DOCUMENT_CODES[$documentType];
            $sequence = $this->peekEntitySequence($entity->id, $entity->code, $documentType, $docCode, $year);

            return $this->formatEntityNumber($entity->code, $year, $docCode, $sequence);
        }

        if (! isset(self::LEGACY_DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        $prefix = self::LEGACY_DOCUMENT_TYPES[$documentType];
        $yearMonth = Carbon::parse($date)->format('Ym');
        $sequence = $this->peekLegacySequence($documentType, $yearMonth);

        return sprintf('%s-%s-%06d', $prefix, $yearMonth, $sequence);
    }

    /**
     * Entity document types that have no verified table/column mapping (counter-only).
     *
     * @return list<string>
     */
    public function getEntityDocumentTypesWithoutVerifiedMapping(): array
    {
        $unmapped = [];
        foreach (array_keys(self::ENTITY_DOCUMENT_CODES) as $documentType) {
            if ($this->resolveVerifiedDocumentNumberSource($documentType) === null) {
                $unmapped[] = $documentType;
            }
        }

        return $unmapped;
    }

    /**
     * Validate a document number format.
     */
    public function validateNumber(string $number, string $documentType): bool
    {
        if ($this->usesEntityFormat($documentType)) {
            return preg_match('/^\d{2}\d{2}\d{2}\d{5}$/', $number) === 1;
        }

        if (! isset(self::LEGACY_DOCUMENT_TYPES[$documentType])) {
            return false;
        }

        $prefix = self::LEGACY_DOCUMENT_TYPES[$documentType];
        $pattern = '/^'.preg_quote($prefix).'-\d{6}-\d{6}$/';

        return preg_match($pattern, $number) === 1;
    }

    /**
     * Get all supported document types.
     */
    public function getSupportedTypes(): array
    {
        return array_unique(array_merge(
            array_keys(self::LEGACY_DOCUMENT_TYPES),
            array_keys(self::ENTITY_DOCUMENT_CODES)
        ));
    }

    /**
     * Repair sequences for a legacy document type (handle gaps).
     */
    public function repairSequences(string $documentType, string $yearMonth): int
    {
        if ($this->usesEntityFormat($documentType)) {
            return 0;
        }

        $prefix = $this->getLegacyPrefix($documentType);
        $existingNumbers = $this->getExistingNumbers($documentType, $yearMonth);

        if (empty($existingNumbers)) {
            return 0;
        }

        $maxSequence = max($existingNumbers);

        $sequence = DocumentSequence::where('document_type', $documentType)
            ->where('year_month', $yearMonth)
            ->first();

        if ($sequence && $sequence->last_sequence < $maxSequence) {
            $sequence->update(['last_sequence' => $maxSequence]);

            return $maxSequence - $sequence->last_sequence;
        }

        return 0;
    }

    /**
     * Determine if document type should use entity-based numbering.
     */
    private function usesEntityFormat(string $documentType): bool
    {
        return array_key_exists($documentType, self::ENTITY_DOCUMENT_CODES);
    }

    private function getLegacyPrefix(string $documentType): string
    {
        if (! isset(self::LEGACY_DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        return self::LEGACY_DOCUMENT_TYPES[$documentType];
    }

    private function peekLegacySequence(string $documentType, string $yearMonth): int
    {
        $sequence = DocumentSequence::where('document_type', $documentType)
            ->where('year_month', $yearMonth)
            ->first();

        return $sequence ? $sequence->last_sequence + 1 : 1;
    }

    /**
     * Fetch next sequence for legacy PREFIX-YYYYMM-###### format.
     */
    private function getNextLegacySequence(string $documentType, string $yearMonth): int
    {
        return DB::transaction(function () use ($documentType, $yearMonth) {
            $sequence = DocumentSequence::lockForUpdate()
                ->where('document_type', $documentType)
                ->where('year_month', $yearMonth)
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::create([
                    'document_type' => $documentType,
                    'year_month' => $yearMonth,
                    'last_sequence' => 0,
                ]);
            }

            $sequence->increment('last_sequence');

            return $sequence->last_sequence;
        });
    }

    private function peekEntitySequence(int $entityId, string $entityCode, string $documentType, string $documentCode, int $year): int
    {
        $sequence = DocumentSequence::where('company_entity_id', $entityId)
            ->where('document_code', $documentCode)
            ->where('year', $year)
            ->first();

        $counterValue = $sequence ? ($sequence->current_number ?? $sequence->last_sequence ?? 0) : 0;
        $maxUsed = $this->getMaxUsedEntitySequence($entityId, $entityCode, $documentType, $documentCode, $year);

        return max($counterValue, $maxUsed) + 1;
    }

    /**
     * Fetch next sequence for entity-based EEYYDD99999 format.
     */
    private function getNextEntitySequence(int $entityId, string $entityCode, string $documentType, string $documentCode, int $year): int
    {
        return DB::transaction(function () use ($entityId, $entityCode, $documentType, $documentCode, $year) {
            $sequence = DocumentSequence::lockForUpdate()
                ->where('company_entity_id', $entityId)
                ->where('document_code', $documentCode)
                ->where('year', $year)
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::create([
                    'company_entity_id' => $entityId,
                    'document_type' => $documentType.'_entity_'.$entityId,
                    'document_code' => $documentCode,
                    'year' => $year,
                    'year_month' => sprintf('%04d00', $year),
                    'last_sequence' => 0,
                    'current_number' => 0,
                ]);

                $sequence = DocumentSequence::lockForUpdate()->findOrFail($sequence->id);
            }

            $counterValue = (int) ($sequence->current_number ?? $sequence->last_sequence ?? 0);
            $maxUsed = $this->getMaxUsedEntitySequence($entityId, $entityCode, $documentType, $documentCode, $year);
            $next = max($counterValue, $maxUsed) + 1;

            return $sequence->setEntitySequence($next);
        });
    }

    /**
     * @return array{table: string, column: string, entity_column: string|null}|null
     */
    private function resolveVerifiedDocumentNumberSource(string $documentType): ?array
    {
        if (array_key_exists($documentType, self::$verifiedEntityDocumentNumberSources)) {
            return self::$verifiedEntityDocumentNumberSources[$documentType];
        }

        if (! isset(self::ENTITY_DOCUMENT_NUMBER_SOURCES[$documentType])) {
            self::$verifiedEntityDocumentNumberSources[$documentType] = null;

            return null;
        }

        $candidate = self::ENTITY_DOCUMENT_NUMBER_SOURCES[$documentType];
        $table = $candidate['table'];
        $column = $candidate['column'];
        $entityColumn = $candidate['entity_column'];

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            self::$verifiedEntityDocumentNumberSources[$documentType] = null;

            return null;
        }

        if ($entityColumn !== null && ! Schema::hasColumn($table, $entityColumn)) {
            self::$verifiedEntityDocumentNumberSources[$documentType] = null;

            return null;
        }

        self::$verifiedEntityDocumentNumberSources[$documentType] = $candidate;

        return $candidate;
    }

    private function getMaxUsedEntitySequence(
        int $entityId,
        string $entityCode,
        string $documentType,
        string $documentCode,
        int $year
    ): int {
        $source = $this->resolveVerifiedDocumentNumberSource($documentType);
        if ($source === null) {
            return 0;
        }

        $prefix = $this->buildEntityNumberPrefix($entityCode, $year, $documentCode);
        $column = $source['column'];
        $table = $source['table'];

        $query = DB::table($table)
            ->where($column, 'like', $prefix.'%')
            ->whereRaw('CHAR_LENGTH(`'.$column.'`) = 11');

        if ($source['entity_column'] !== null) {
            $query->where($source['entity_column'], $entityId);
        }

        $max = $query->selectRaw('MAX(CAST(RIGHT(`'.$column.'`, 5) AS UNSIGNED)) as max_seq')
            ->value('max_seq');

        return (int) ($max ?? 0);
    }

    private function buildEntityNumberPrefix(string $entityCode, int $year, string $documentCode): string
    {
        $entityPart = str_pad(substr(preg_replace('/\D/', '', $entityCode), -2) ?: $entityCode, 2, '0', STR_PAD_LEFT);
        $yearPart = substr((string) $year, -2);

        return sprintf('%s%s%s', $entityPart, $yearPart, $documentCode);
    }

    private function formatEntityNumber(string $entityCode, int $year, string $documentCode, int $sequence): string
    {
        $prefix = $this->buildEntityNumberPrefix($entityCode, $year, $documentCode);
        $sequencePart = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

        return $prefix.$sequencePart;
    }

    /**
     * Placeholder for legacy data reconciliation.
     */
    private function getExistingNumbers(string $documentType, string $yearMonth): array
    {
        return [];
    }
}
