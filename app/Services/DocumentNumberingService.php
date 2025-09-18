<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentNumberingService
{
    const DOCUMENT_TYPES = [
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
        'journal' => 'JNL'
    ];

    /**
     * Generate a document number for the given document type and date
     */
    public function generateNumber(string $documentType, string $date, array $options = []): string
    {
        if (!isset(self::DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        $prefix = self::DOCUMENT_TYPES[$documentType];
        $yearMonth = Carbon::parse($date)->format('Ym');
        $sequence = $this->getNextSequence($documentType, $yearMonth);

        return sprintf('%s-%s-%06d', $prefix, $yearMonth, $sequence);
    }

    /**
     * Get the next sequence number for a document type and year-month
     */
    public function getNextSequence(string $documentType, string $yearMonth): int
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
                    'last_sequence' => 0
                ]);
            }

            $sequence->increment('last_sequence');
            return $sequence->last_sequence;
        });
    }

    /**
     * Validate a document number format
     */
    public function validateNumber(string $number, string $documentType): bool
    {
        if (!isset(self::DOCUMENT_TYPES[$documentType])) {
            return false;
        }

        $prefix = self::DOCUMENT_TYPES[$documentType];
        $pattern = '/^' . preg_quote($prefix) . '-\d{6}-\d{6}$/';

        return preg_match($pattern, $number) === 1;
    }

    /**
     * Get all supported document types
     */
    public function getSupportedTypes(): array
    {
        return array_keys(self::DOCUMENT_TYPES);
    }

    /**
     * Get prefix for a document type
     */
    public function getPrefix(string $documentType): string
    {
        if (!isset(self::DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        return self::DOCUMENT_TYPES[$documentType];
    }

    /**
     * Repair sequences for a document type (handle gaps)
     */
    public function repairSequences(string $documentType, string $yearMonth): int
    {
        $prefix = $this->getPrefix($documentType);
        $pattern = $prefix . '-' . $yearMonth . '-%';

        // Get all existing numbers for this type and month
        $existingNumbers = $this->getExistingNumbers($documentType, $yearMonth);

        if (empty($existingNumbers)) {
            return 0;
        }

        // Find the highest sequence
        $maxSequence = max($existingNumbers);

        // Update the sequence record
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
     * Get existing document numbers for a type and month
     */
    private function getExistingNumbers(string $documentType, string $yearMonth): array
    {
        $prefix = $this->getPrefix($documentType);
        $pattern = $prefix . '-' . $yearMonth . '-%';

        // This would need to be implemented based on the specific table structure
        // For now, return empty array as placeholder
        return [];
    }
}
