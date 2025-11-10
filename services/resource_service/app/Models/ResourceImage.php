<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

class ResourceImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'file_path',
        'order_index',
        'alt_text',
    ];
    protected $appends = ['full_image_url'];

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
    public function getFullImageUrlAttribute()
    {
        return url('storage/' . $this->image_path);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($resourceImage) {
            // Delete the image file from storage
            if (\Storage::disk('public')->exists($resourceImage->image_path)) {
                \Storage::disk('public')->delete($resourceImage->image_path);
            }
        });
    }
}
