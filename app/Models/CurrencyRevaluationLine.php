<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyRevaluationLine extends Model
{
    protected $fillable = [
        'revaluation_id',
        'account_id',
        'business_partner_id',
        'document_type',
        'document_id',
        'original_amount',
        'original_currency_id',
        'original_exchange_rate',
        'revaluation_amount',
        'revaluation_exchange_rate',
        'unrealized_gain_loss',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'original_exchange_rate' => 'decimal:6',
        'revaluation_amount' => 'decimal:2',
        'revaluation_exchange_rate' => 'decimal:6',
        'unrealized_gain_loss' => 'decimal:2',
    ];

    // Relationships
    public function revaluation(): BelongsTo
    {
        return $this->belongsTo(CurrencyRevaluation::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class);
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    public function originalCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'original_currency_id');
    }

    // Helper methods
    public function isGain(): bool
    {
        return $this->unrealized_gain_loss > 0;
    }

    public function isLoss(): bool
    {
        return $this->unrealized_gain_loss < 0;
    }

    public function getDocumentAttribute()
    {
        if (!$this->document_type || !$this->document_id) {
            return null;
        }

        $modelClass = null;

        switch ($this->document_type) {
            case 'purchase_invoice':
                $modelClass = \App\Models\Accounting\PurchaseInvoice::class;
                break;
            case 'sales_invoice':
                $modelClass = \App\Models\Accounting\SalesInvoice::class;
                break;
            case 'bank_account':
                $modelClass = \App\Models\BankAccount::class;
                break;
            default:
                return null;
        }

        return $modelClass::find($this->document_id);
    }

    public function getDocumentDisplayAttribute(): string
    {
        $document = $this->getDocumentAttribute();

        if (!$document) {
            return 'N/A';
        }

        // Try to get document number property
        $numberProperty = null;
        if (isset($document->invoice_no)) {
            $numberProperty = 'invoice_no';
        } elseif (isset($document->code)) {
            $numberProperty = 'code';
        } elseif (isset($document->id)) {
            $numberProperty = 'id';
        }

        return $numberProperty ? $document->$numberProperty : 'N/A';
    }

    // Static methods
    public static function calculateGainLoss($originalAmount, $originalRate, $newRate): float
    {
        $originalIdrAmount = $originalAmount * $originalRate;
        $newIdrAmount = $originalAmount * $newRate;

        return $newIdrAmount - $originalIdrAmount;
    }
}
