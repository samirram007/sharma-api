<?php

namespace Modules\RolePermission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\Role\Models\Role;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'role_permissions';

    protected $fillable = ['role_id', 'app_module_feature_id', 'is_allowed'];

    public $timestamps = false;

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(AppModuleFeature::class, 'app_module_feature_id');
    }
}
