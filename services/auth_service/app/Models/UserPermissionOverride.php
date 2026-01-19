<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermissionOverride extends Model
{
    use HasFactory;

    // These fields must match your migration
    protected $fillable = [
        'user_id',
        'permission_slug',
        'is_allowed', 
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    /**
     * Relationship back to the User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}