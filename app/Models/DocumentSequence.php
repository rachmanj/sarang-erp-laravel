<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'year_month',
        'last_sequence'
    ];

    protected $casts = [
        'last_sequence' => 'integer'
    ];

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
}
