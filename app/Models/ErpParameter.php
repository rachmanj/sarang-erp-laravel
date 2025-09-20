<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpParameter extends Model
{
    protected $fillable = [
        'category',
        'parameter_key',
        'parameter_name',
        'parameter_value',
        'data_type',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Get the user who created this parameter
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this parameter
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active parameters
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for parameters by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get parameter value with type casting
     */
    public function getValueAttribute()
    {
        switch ($this->data_type) {
            case 'integer':
                return (int) $this->parameter_value;
            case 'boolean':
                return (bool) $this->parameter_value;
            case 'json':
                return json_decode($this->parameter_value, true);
            default:
                return $this->parameter_value;
        }
    }

    /**
     * Set parameter value with type conversion
     */
    public function setValueAttribute($value)
    {
        switch ($this->data_type) {
            case 'integer':
                $this->parameter_value = (string) $value;
                break;
            case 'boolean':
                $this->parameter_value = $value ? '1' : '0';
                break;
            case 'json':
                $this->parameter_value = json_encode($value);
                break;
            default:
                $this->parameter_value = (string) $value;
        }
    }
}
