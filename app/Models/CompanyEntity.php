<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyEntity extends Model
{
    protected $fillable = [
        'code',
        'name',
        'legal_name',
        'tax_number',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'letterhead_meta',
        'is_active',
    ];

    protected $casts = [
        'letterhead_meta' => 'array',
        'is_active' => 'boolean',
    ];
}
