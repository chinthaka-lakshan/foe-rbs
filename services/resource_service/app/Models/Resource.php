<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resource extends Model
{
    protected $fillable = [
        'name',
        'location_name',
        'description',
        'category_id',
        'assigned_admin_id',
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
}