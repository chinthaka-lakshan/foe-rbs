<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Category;
use App\Models\TemplateField;
use App\Models\Resource;

class ResourceTemplate extends Model
{
    protected $fillable = [
        'template_name',
        'category_id',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(TemplateField::class, 'template_id')->orderBy('order_index');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'template_id');
    }
}
