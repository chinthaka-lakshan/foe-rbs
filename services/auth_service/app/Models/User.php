<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'status', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function getAllPermissions(): array
    {
        // 1. Identify the primary role name
        $roleName = $this->roles()->first()?->name;

        // 2. Define standard role defaults
        $roleDefaults = [
            'Master Admin' => ['*'],
            'Admin'        => ['resource.create', 'resource.update', 'resource.view', 'resource.delete', 'user.index'],
            'User'         => ['resource.view', 'booking.create'],
        ];

        $permissions = $roleDefaults[$roleName] ?? [];

        // Master Admin has everything; we can return early unless you specifically want to deny Master Admin things
        if ($roleName === 'Master Admin') {
            return ['*'];
        }

        // 3. Apply User-Specific Overrides from database
        $overrides = $this->permissionOverrides()->get();
        foreach ($overrides as $override) {
            if ($override->is_allowed) {
                // "Specific Allow": add if not already present
                if (!in_array($override->permission_slug, $permissions)) {
                    $permissions[] = $override->permission_slug;
                }
            } else {
                // "Specific Deny": remove if present
                $permissions = array_filter($permissions, fn($p) => $p !== $override->permission_slug);
            }
        }

        return array_values($permissions);
    }
}