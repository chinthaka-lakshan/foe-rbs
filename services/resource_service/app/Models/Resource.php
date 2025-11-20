<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Resource extends Model
{
    protected $fillable = [
        'name',
        'location_name',
        'description',
        'base_price',
        'category_id',
        'assigned_admin_id',
        'tamplate_data' => 'array',
        'template_id',
        'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function resourceImages()
    {
        return $this->hasMany(ResourceImage::class, 'resource_id')->orderBy('order_index');
    }
    public function equipment(): HasMany
    {
        return $this->hasMany(ResourceEquipment::class);
    }
    public function availability(): HasMany
    {
        return $this->hasMany(ResourceAvailability::class)->orderBy('day_of_week');
    }
    public function template(): BelongsTo
    {
        return $this->belongsTo(ResourceTemplate::class, 'template_id');
    }
}