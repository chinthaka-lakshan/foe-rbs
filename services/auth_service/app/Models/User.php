<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }


    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function hasPermission(string $permission): bool
    {
        // 1. Check for Manual Overrides first
        $override = $this->permissionOverrides()
                         ->where('permission_slug', $permission)
                         ->first();

        if ($override !== null) {
            return (bool) $override->is_granted;
        }

        // 2. Fallback to Role-Based Defaults
        return $this->checkRoleDefault($this->role, $permission);
    }

    private function checkRoleDefault(string $role, string $permission): bool
    {
        $rolePermissions = [
            'master_admin' => ['*'],
            'admin' => ['resource.create', 'resource.update', 'resource.view', 'resource.delete'],
            'user'  => ['resource.view', 'booking.create'],
        ];

        if (in_array('*', $rolePermissions[$role] ?? [])) return true;

        return in_array($permission, $rolePermissions[$role] ?? []);
    }
}
