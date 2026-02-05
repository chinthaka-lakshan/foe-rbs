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
    // Use necessary traits
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role'
    ];

    // Hide sensitive attributes
    protected $hidden = [
        'password',
        'remember_token'
    ];

    // Cast attributes
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Define relationship with UserPermissionOverride
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    // Define many-to-many relationship with Role model
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // Get all permissions considering role defaults and user-specific overrides
    public function getAllPermissions(): array
    {
        //Identify the primary role name
        $roleName = $this->roles()->first()?->name;

        //Define standard role defaults
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
        //Apply User-Specific Overrides from database
        $overrides = $this->permissionOverrides()->get();
        foreach ($overrides as $override) {
            if ($override->is_allowed) {
                if (!in_array($override->permission_slug, $permissions)) {
                    $permissions[] = $override->permission_slug;
                }
            } else {
                $permissions = array_filter($permissions, fn($p) => $p !== $override->permission_slug);
            }
        }

        return array_values($permissions);
    }
}