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
    protected $appends = ['image_url'];

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
    // public function getImageUrlAttribute()
    // {
    //     return asset('storage/' . $this->image_path);
    // }

    public function getImageUrlAttribute()
    {
        if ($this->file_path) {
            return 'http://localhost:8000/storage/' . $this->file_path;
        }
        return null;
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            if (\Storage::disk('public')->exists($image->file_path)) {
                \Storage::disk('public')->delete($image->file_path);
            }
        });
    }
}
