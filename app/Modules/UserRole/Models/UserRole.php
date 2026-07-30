<?php

namespace Modules\UserRole\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

class UserRole extends Model
{
    use HasFactory;

    protected $table = 'user_roles';

    public $timestamps = false; // set true if you have timestamps

    protected $fillable = [
        'user_id',
        'role_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permissions()
    {
        return $this->role->permissions();
    }

    public function features(): HasManyThrough
    {
        return $this->hasManyThrough(
            'Modules\AppModuleFeature\Models\AppModuleFeature',
            'Modules\RolePermission\Models\RolePermission',
            'role_id', // Foreign key on RolePermission table...
            'id', // Foreign key on Feature table...
            'role_id', // Local key on UserRole table...
            'app_module_feature_id' // Local key on RolePermission table...
        );
    }
}
