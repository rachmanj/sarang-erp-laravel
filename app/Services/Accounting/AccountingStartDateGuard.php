<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AccountingStartDateGuard
{
    /** @var array<class-string<Model>, string> */
    private const DATE_ATTRIBUTE_BY_MODEL = [
        \App\Models\GRGIHeader::class => 'transaction_date',
        \App\Models\DeliveryOrder::class => 'planned_delivery_date',
    ];

    public static function validateModel(Model $model): void
    {
        $dateAttribute = self::dateAttributeFor($model);

        if ($model->exists && ! $model->isDirty($dateAttribute)) {
            return;
        }

        $date = $model->getAttribute($dateAttribute);
        if ($date === null || $date === '') {
            return;
        }

        if (self::modelIsExempt($model)) {
            return;
        }

        self::assertDateAllowed($date, $dateAttribute);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function validateJournalPayload(array $payload): void
    {
        if (($payload['source_type'] ?? null) === 'manual_journal') {
            return;
        }

        $date = $payload['date'] ?? null;
        if ($date === null || $date === '') {
            return;
        }

        self::assertDateAllowed($date, 'date');
    }

    public static function assertDateAllowed(mixed $date, string $errorKey = 'date'): void
    {
        $documentDate = Carbon::parse($date)->startOfDay();
        $startDate = Carbon::parse((string) config('accounting.start_date'))->startOfDay();

        if ($documentDate->lt($startDate)) {
            throw ValidationException::withMessages([
                $errorKey => [self::message()],
            ]);
        }
    }

    public static function message(): string
    {
        $start = Carbon::parse((string) config('accounting.start_date'))->locale('id');

        return sprintf(
            'Tanggal dokumen tidak boleh sebelum periode pembukuan yang dimulai %s.',
            $start->translatedFormat('j F Y')
        );
    }

    private static function dateAttributeFor(Model $model): string
    {
        return self::DATE_ATTRIBUTE_BY_MODEL[$model::class] ?? 'date';
    }

    private static function modelIsExempt(Model $model): bool
    {
        if ($model instanceof Journal && $model->source_type === 'manual_journal') {
            return true;
        }

        if (! self::modelHasOpeningBalanceFlag($model)) {
            return false;
        }

        return self::isTruthyOpeningBalance($model->getAttribute('is_opening_balance'));
    }

    private static function modelHasOpeningBalanceFlag(Model $model): bool
    {
        return in_array('is_opening_balance', $model->getFillable(), true)
            || array_key_exists('is_opening_balance', $model->getAttributes());
    }

    private static function isTruthyOpeningBalance(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }
}
