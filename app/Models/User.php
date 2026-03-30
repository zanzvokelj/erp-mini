<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BelongsToCompany, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSales(): bool
    {
        return $this->role === 'sales';
    }

    public function isWarehouse(): bool
    {
        return $this->role === 'warehouse';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    public function permissions(): array
    {
        return config('rbac.roles.' . $this->role, []);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions();

        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true);
    }

    public function canAccessApp(): bool
    {
        return $this->company_id !== null
            && array_key_exists($this->role, config('rbac.roles', []))
            && $this->hasPermission('app.access');
    }

    public function canAccessApi(): bool
    {
        return $this->canAccessApp() && $this->hasPermission('api.access');
    }

    public function hasAllowedAdminAccess(): bool
    {
        return $this->canAccessApp();
    }
}
