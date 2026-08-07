<?php

namespace App\Services\Accounting;

use App\Models\ErpParameter;

final class PaymentRoundingService
{
    public function salesReceiptTolerance(): float
    {
        return (float) ErpParameter::get('sales_receipt_rounding_tolerance', 999999);
    }

    public function purchasePaymentTolerance(): float
    {
        return (float) ErpParameter::get('purchase_payment_rounding_tolerance', 999999);
    }

    public function defaultRoundingAccountId(): ?int
    {
        $id = ErpParameter::get('rounding_account_id');

        return $id ? (int) $id : null;
    }

    /**
     * @return array{rounding_amount: float, rounding_account_id: int|null, error: string|null}
     */
    public function resolve(float $cashTotal, float $allocationTotal, float $tolerance, ?int $roundingAccountId): array
    {
        $cashCents = (int) round($cashTotal * 100);
        $allocationCents = (int) round($allocationTotal * 100);
        $roundingCents = $cashCents - $allocationCents;
        $roundingAmount = $roundingCents / 100;

        if ($roundingCents === 0) {
            return [
                'rounding_amount' => 0.0,
                'rounding_account_id' => null,
                'error' => null,
            ];
        }

        if (abs($roundingAmount) > $tolerance) {
            return [
                'rounding_amount' => $roundingAmount,
                'rounding_account_id' => null,
                'error' => 'Cash total differs from allocation total by more than the allowed rounding tolerance.',
            ];
        }

        $accountId = $roundingAccountId ?: $this->defaultRoundingAccountId();

        if (! $accountId) {
            return [
                'rounding_amount' => $roundingAmount,
                'rounding_account_id' => null,
                'error' => 'A rounding account is required when cash total does not match allocation total.',
            ];
        }

        return [
            'rounding_amount' => $roundingAmount,
            'rounding_account_id' => $accountId,
            'error' => null,
        ];
    }
}
