<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpEmbedding extends Model
{
    protected $fillable = [
        'chunk_key',
        'source_path',
        'heading',
        'locale',
        'content',
        'embedding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }
}
