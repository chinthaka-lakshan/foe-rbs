<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ResourceTemplate;

class TemplateField extends Model
{
    protected $fillable = [
        'template_id',
        'field_name',
        'field_key',
        'field_type',
        'is_required',
        'order_index',
        'placeholder',
        'default_value',
    ];

    protected $casts = [
        'template_id' => 'integer',
        'is_required' => 'boolean',
        'order_index' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ResourceTemplate::class, 'template_id');
    }

    /**
     * Generate field key from field name
     */
    public static function generateFieldKey(string $fieldName): string
    {
        return strtolower(str_replace(' ', '_', trim($fieldName)));
    }
}
