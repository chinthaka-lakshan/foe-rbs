<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateField extends Model
{
    protected $fillable = [
        'template_id', 'field_name', 'field_key', 'field_type', 
        'is_required', 'order_index', 'placeholder', 'default_value', 'metadata'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'metadata' => 'array', // Automatically handles JSON string <-> PHP array
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ResourceTemplate::class);
    }

    public static function generateFieldKey(string $fieldName): string
    {
        return strtolower(str_replace(' ', '_', trim($fieldName)));
    }
}