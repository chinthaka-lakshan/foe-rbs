<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermissionOverride extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'permission_slug',
        'is_granted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
