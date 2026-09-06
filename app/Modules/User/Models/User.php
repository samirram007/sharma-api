<?php

namespace Modules\User\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Modules\Role\Models\Role;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'user_type',
        'password',
        'status',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function user_fiscal_year(): HasOne
    {
        return $this->hasOne(UserFiscalYear::class, 'user_id', 'id');
    }

    public function current_fiscal_year(): ?UserFiscalYear
    {
        return $this->user_fiscal_year()->first();
    }

    public function fiscal_years(): BelongsToMany
    {
        return $this->belongsToMany(
            'Modules\FiscalYear\Models\FiscalYear',
            'user_fiscal_years',
            'user_id',
            'fiscal_year_id'
        );
    }

    public function user_roles(): HasMany
    {
        return $this->hasMany('Modules\UserRole\Models\UserRole', 'user_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    // Helper: Check if user has a specific role
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    // Helper: Assign a role to the user
    public function assignRole($role): void
    {
        $roleId = $role instanceof Role ? $role->id : Role::where('name', $role)->value('id');
        if ($roleId) {
            $this->roles()->syncWithoutDetaching([$roleId]);
        }
    }

    /**
     * Users holding the DEVELOPER role are only visible to developers — every
     * other role/user never sees them. Apply to any user-listing query so
     * developer accounts stay invisible outside the developer's own view.
     */
    public function scopeExcludeDevelopersForNonDevelopers(Builder $query): Builder
    {
        if (Auth::check() && Auth::user()->roles()->where('code', 'DEVELOPER')->exists()) {
            return $query;
        }

        return $query->whereDoesntHave('roles', fn (Builder $q) => $q->where('code', 'DEVELOPER'));
    }

    public function userable()
    {
        return $this->morphTo();
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
