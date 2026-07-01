<?php

namespace Modules\Menu\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AppModuleFeature\Models\AppModuleFeature;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';

    protected $fillable = [
        'app_module_feature_id',
        'menu_name',
        'route',
        'icon',
        'parent_id',
        'sort_order',
        'status',
        'is_visible',
        'description',
        'is_group',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_group' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * The feature/permission this menu entry is linked to.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(AppModuleFeature::class, 'app_module_feature_id', 'id');
    }

    /**
     * Parent menu item (for hierarchical/nested menus).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
