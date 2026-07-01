<?php

namespace Modules\AppModuleFeature\Models;

use Modules\AppModule\Models\AppModule;
use Modules\Menu\Models\Menu;
use Modules\RolePermission\Models\RolePermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppModuleFeature extends Model
{
    use HasFactory;

    protected $table = 'app_module_features';

    protected $fillable = [
        'app_module_id',
        'name',
        'code',
        'description',
        'status',
        'action',

    ];

    protected $casts = [
    ];
    public function module(): BelongsTo
    {
        return $this->belongsTo(AppModule::class, 'app_module_id', 'id');
    }

    public function role_permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'app_module_feature_id', 'id');
    }

    /**
     * Menu entries linked to this feature.
     */
    public function feature_menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'app_module_feature_id');
    }
}
