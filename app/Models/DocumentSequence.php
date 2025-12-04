<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'year_month',
        'last_sequence',
        'company_entity_id',
        'document_code',
        'year',
        'current_number',
    ];

    protected $casts = [
        'last_sequence' => 'integer',
        'company_entity_id' => 'integer',
        'year' => 'integer',
        'current_number' => 'integer',
    ];

    public function companyEntity()
    {
        return $this->belongsTo(CompanyEntity::class);
    }

    // Scopes
    public function scopeForType($query, string $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    public function scopeForMonth($query, string $yearMonth)
    {
        return $query->where('year_month', $yearMonth);
    }

    public function scopeForTypeAndMonth($query, string $documentType, string $yearMonth)
    {
        return $query->where('document_type', $documentType)
            ->where('year_month', $yearMonth);
    }

    // Helper methods
    public function getNextSequence(): int
    {
        return $this->last_sequence + 1;
    }

    public function incrementSequence(): int
    {
        $this->increment('last_sequence');
        return $this->last_sequence;
    }

    public function incrementEntitySequence(): int
    {
        $this->increment('current_number');
        $this->last_sequence = $this->current_number;
        $this->save();

        return $this->current_number;
    }
}
