<?php

namespace Modules\Menu\Services;

use Modules\Menu\Contracts\MenuServiceInterface;
use Modules\Menu\Models\Menu;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class MenuService implements  MenuServiceInterface
{
    protected $resource = ['feature.module'];

    public function getAll(): Collection
    {
       
        return Menu::with($this->resource)
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    public function getById(int $id): ?Menu
    {
        return Menu::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): Menu
    {
        return Menu::create($data);
    }

    public function update(array $data, int $id): Menu
    {
        $record = Menu::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = Menu::findOrFail($id);
        return $record->delete();
    }

    /** Build the full hierarchical menu tree for admin management (up to 3 levels). */
    public function getTree(): Collection
    {
        return Menu::with([
            'feature',
            'children' => fn($q) => $q->orderBy('sort_order'),
            'children.feature',
            'children.children' => fn($q) => $q->orderBy('sort_order'),
            'children.children.feature',
            'children.children.children' => fn($q) => $q->orderBy('sort_order'),
            'children.children.children.feature',
        ])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    /** Get child menus for a given parent. */
    public function getChildren(int $parentId): Collection
    {
        return Menu::with($this->resource)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Batch reorder menu items — updates sort_order and parent_id for each item.
     * Accepts an array of ['id' => int, 'sort_order' => int, 'parent_id' => int|null].
     */
    public function reorder(array $items): bool
    {
        foreach ($items as $item) {
            if (!isset($item['id'])) {
                continue;
            }
            $update = ['sort_order' => $item['sort_order'] ?? 0];
            if (array_key_exists('parent_id', $item)) {
                $update['parent_id'] = $item['parent_id'];
            }
            Menu::where('id', $item['id'])->update($update);
        }
        return true;
    }

    /**
     * Collect all allowed feature codes for the authenticated user's roles.
     */
    public function getUserMenuPermissions(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $permissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->is_allowed && $permission->feature) {
                    $permissions[] = $permission->feature->code;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Build and return the hierarchical menu tree filtered by the
     * authenticated user's role permissions.
     */
    public function getUserMenuTree(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        // Collect all allowed feature IDs across the user's roles
        $allowedFeatureIds = collect();
        foreach ($user->roles as $role) {
            $allowedFeatureIds = $allowedFeatureIds->merge(
                $role->permissions
                    ->where('is_allowed', true)
                    ->pluck('app_module_feature_id')
            );
        }
        $allowedFeatureIds = $allowedFeatureIds->unique()->values()->toArray();

        if (empty($allowedFeatureIds)) {
            return [];
        }

        // Fetch root-level menu groups that the user has permission for,
        // loading up to 3 levels of children
        $rootMenus = Menu::with([
            'children' => function ($query) use ($allowedFeatureIds) {
                $query->whereIn('app_module_feature_id', $allowedFeatureIds)
                    ->where('status', 'active')
                    ->where('is_visible', true)
                    ->orderBy('sort_order');
            },
            'children.feature',
            'children.children' => function ($query) use ($allowedFeatureIds) {
                $query->whereIn('app_module_feature_id', $allowedFeatureIds)
                    ->where('status', 'active')
                    ->where('is_visible', true)
                    ->orderBy('sort_order');
            },
            'children.children.feature',
            'children.children.children' => function ($query) use ($allowedFeatureIds) {
                $query->whereIn('app_module_feature_id', $allowedFeatureIds)
                    ->where('status', 'active')
                    ->where('is_visible', true)
                    ->orderBy('sort_order');
            },
            'children.children.children.feature',
            'feature',
        ])
            ->whereNull('parent_id')
            ->whereIn('app_module_feature_id', $allowedFeatureIds)
            ->where('status', 'active')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        return $this->buildMenuTree($rootMenus);
    }

    /**
     * Get all AppModuleFeatures with their permission status for a given role.
     */
    public function getRoleMenuPermissions(int $roleId): Collection
    {
        return AppModuleFeature::with(['role_permissions' => function ($q) use ($roleId) {
            $q->where('role_id', $roleId);
        }])->get();
    }

    /**
     * Recursively build a menu tree array from Eloquent models.
     */
    private function buildMenuTree($menus): array
    {
        $tree = [];
        foreach ($menus as $menu) {
            $node = [
                'id'          => $menu->id,
                'menuName'    => $menu->menu_name,
                'route'       => $menu->route,
                'icon'        => $menu->icon,
                'isGroup'     => $menu->is_group,
                'sortOrder'   => $menu->sort_order,
                'featureCode' => $menu->feature?->code,
                'children'    => [],
            ];

            if ($menu->relationLoaded('children') && $menu->children->isNotEmpty()) {
                $node['children'] = $this->buildMenuTree($menu->children);
            }

            $tree[] = $node;
        }
        return $tree;
    }
}
