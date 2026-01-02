<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        'account_statement' => 'AST'
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
        'sales_receipt' => '09',
        'asset_disposal' => '10',
        'cash_expense' => '11',
        'journal' => '12',
        'account_statement' => '13',
    ];

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
            $sequence = $this->getNextEntitySequence($entity->id, $documentType, $docCode, $year);

            return $this->formatEntityNumber($entity->code, $year, $docCode, $sequence);
        }

        if (!isset(self::LEGACY_DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        $prefix = self::LEGACY_DOCUMENT_TYPES[$documentType];
        $yearMonth = Carbon::parse($date)->format('Ym');
        $sequence = $this->getNextLegacySequence($documentType, $yearMonth);

        return sprintf('%s-%s-%06d', $prefix, $yearMonth, $sequence);
    }

    /**
     * Validate a document number format.
     */
    public function validateNumber(string $number, string $documentType): bool
    {
        if ($this->usesEntityFormat($documentType)) {
            return preg_match('/^\d{2}\d{2}\d{2}\d{5}$/', $number) === 1;
        }

        if (!isset(self::LEGACY_DOCUMENT_TYPES[$documentType])) {
            return false;
        }

        $prefix = self::LEGACY_DOCUMENT_TYPES[$documentType];
        $pattern = '/^' . preg_quote($prefix) . '-\d{6}-\d{6}$/';

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
        if (!isset(self::LEGACY_DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        return self::LEGACY_DOCUMENT_TYPES[$documentType];
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

            if (!$sequence) {
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

    /**
     * Fetch next sequence for entity-based EEYYDD99999 format.
     */
    private function getNextEntitySequence(int $entityId, string $documentType, string $documentCode, int $year): int
    {
        return DB::transaction(function () use ($entityId, $documentType, $documentCode, $year) {
            $sequence = DocumentSequence::lockForUpdate()
                ->where('company_entity_id', $entityId)
                ->where('document_code', $documentCode)
                ->where('year', $year)
                ->first();

            if (!$sequence) {
                $sequence = DocumentSequence::create([
                    'company_entity_id' => $entityId,
                    'document_type' => $documentType . '_entity_' . $entityId,
                    'document_code' => $documentCode,
                    'year' => $year,
                    'year_month' => sprintf('%04d00', $year),
                    'last_sequence' => 0,
                    'current_number' => 0,
                ]);
            }

            return $sequence->incrementEntitySequence();
        });
    }

    private function formatEntityNumber(string $entityCode, int $year, string $documentCode, int $sequence): string
    {
        $entityPart = str_pad(substr(preg_replace('/\D/', '', $entityCode), -2) ?: $entityCode, 2, '0', STR_PAD_LEFT);
        $yearPart = substr((string) $year, -2);
        $sequencePart = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

        return sprintf('%s%s%s%s', $entityPart, $yearPart, $documentCode, $sequencePart);
    }

    /**
     * Placeholder for legacy data reconciliation.
     */
    private function getExistingNumbers(string $documentType, string $yearMonth): array
    {
        return [];
    }
}
