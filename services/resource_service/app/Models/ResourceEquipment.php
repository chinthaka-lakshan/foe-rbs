<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Resource;

class ResourceEquipment extends Model
{
    protected $table = 'resource_equipment';
    protected $fillable = [
        'resource_id',
        'equipment_name',
        'quantity',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
