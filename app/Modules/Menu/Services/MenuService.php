<?php

namespace Modules\Menu\Services;

use App\Support\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\Menu\Contracts\MenuServiceInterface;
use Modules\Menu\Models\Menu;

class MenuService extends BaseService implements MenuServiceInterface
{
    protected string $modelClass = Menu::class;

    protected array $defaultResource = ['feature.module'];

    /** Build the full hierarchical menu tree for admin management (up to 3 levels). */
    public function getTree(): Collection
    {
        return Menu::with([
            'feature',
            'children' => fn ($q) => $q->orderBy('sort_order'),
            'children.feature',
            'children.children' => fn ($q) => $q->orderBy('sort_order'),
            'children.children.feature',
            'children.children.children' => fn ($q) => $q->orderBy('sort_order'),
            'children.children.children.feature',
        ])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    /** Get child menus for a given parent. */
    public function getChildren(int $parentId): Collection
    {
        return Menu::with($this->defaultResource)
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
            if (! isset($item['id'])) {
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
     * Batch update multiple menu items with the same data.
     */
    public function batchUpdate(array $ids, array $data): int
    {
        return Menu::whereIn('id', $ids)->update($data);
    }

    /**
     * Batch delete multiple menu items.
     */
    public function batchDelete(array $ids): int
    {
        // Delete children first to avoid FK constraints
        Menu::whereIn('parent_id', $ids)->delete();

        return Menu::whereIn('id', $ids)->delete();
    }

    /**
     * Duplicate a menu entry and all its children recursively.
     * Returns the newly created root menu item.
     */
    public function duplicate(int $id): Menu
    {
        $original = Menu::findOrFail($id);

        // Recursively clone the tree
        $clone = $this->deepClone($original, null);

        return $clone->fresh();
    }

    /**
     * Recursively clone a menu item and its children.
     */
    private function deepClone(Menu $menu, ?int $newParentId): Menu
    {
        $data = $menu->replicate(['id', 'created_at', 'updated_at'])->toArray();
        $data['menu_name'] = $data['menu_name'].' (Copy)';
        $data['parent_id'] = $newParentId;

        // Bump sort_order so the copy appears right after the original
        $data['sort_order'] = $menu->sort_order + 1;

        // Shift existing siblings to make room
        Menu::where('parent_id', $menu->parent_id)
            ->where('sort_order', '>=', $data['sort_order'])
            ->increment('sort_order');

        $clone = Menu::create($data);

        // Recursively clone children
        $children = Menu::where('parent_id', $menu->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($children as $child) {
            $this->deepClone($child, $clone->id);
        }

        return $clone;
    }

    /**
     * Export all menu entries as a nested tree array for JSON download.
     */
    public function exportJson(): array
    {
        $rootMenus = Menu::with([
            'children' => fn ($q) => $q->orderBy('sort_order'),
            'children.children' => fn ($q) => $q->orderBy('sort_order'),
            'children.children.children' => fn ($q) => $q->orderBy('sort_order'),
        ])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return $this->buildExportTree($rootMenus);
    }

    /**
     * Import menu entries from a JSON array.
     * Accepts both nested and flat (with parent_ref) formats.
     * Returns the number of entries created.
     */
    public function importJson(array $items): int
    {
        $count = 0;

        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);

            $item['sort_order'] = $item['sort_order'] ?? (($count + 1) * 10);
            $item['status'] = $item['status'] ?? 'active';
            $item['is_visible'] = $item['is_visible'] ?? true;

            $created = Menu::create($item);
            $count++;

            if (! empty($children)) {
                $count += $this->importNestedChildren($children, $created->id);
            }
        }

        return $count;
    }

    /**
     * Recursively import nested children.
     */
    private function importNestedChildren(array $children, int $parentId): int
    {
        $count = 0;
        foreach ($children as $i => $child) {
            $grandchildren = $child['children'] ?? [];
            unset($child['children']);

            $child['parent_id'] = $parentId;
            $child['sort_order'] = $child['sort_order'] ?? (($i + 1) * 10);
            $child['status'] = $child['status'] ?? 'active';
            $child['is_visible'] = $child['is_visible'] ?? true;

            $created = Menu::create($child);
            $count++;

            if (! empty($grandchildren)) {
                $count += $this->importNestedChildren($grandchildren, $created->id);
            }
        }

        return $count;
    }

    /**
     * Recursively build an export-friendly tree array.
     */
    private function buildExportTree($menus): array
    {
        $tree = [];
        foreach ($menus as $menu) {
            $node = [
                'menu_name' => $menu->menu_name,
                'route' => $menu->route,
                'icon' => $menu->icon,
                'is_group' => $menu->is_group,
                'sort_order' => $menu->sort_order,
                'status' => $menu->status,
                'is_visible' => $menu->is_visible,
                'description' => $menu->description,
                'children' => [],
            ];

            if ($menu->relationLoaded('children') && $menu->children->isNotEmpty()) {
                $node['children'] = $this->buildExportTree($menu->children);
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * Search menu entries with pagination.
     * Searches by menu_name, route, and feature code.
     */
    public function search(string $query, int $perPage = 20): LengthAwarePaginator
    {
        $like = '%'.$query.'%';

        return Menu::with($this->defaultResource)
            ->where(function ($q) use ($like) {
                $q->where('menu_name', 'like', $like)
                    ->orWhere('route', 'like', $like)
                    ->orWhereHas('feature', function ($fq) use ($like) {
                        $fq->where('code', 'like', $like)
                            ->orWhere('name', 'like', $like);
                    });
            })
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    /**
     * Collect all allowed feature codes for the authenticated user's roles.
     */
    public function getUserMenuPermissions(): array
    {
        $user = Auth::user();
        if (! $user) {
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
        if (! $user) {
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
                'id' => $menu->id,
                'menuName' => $menu->menu_name,
                'route' => $menu->route,
                'icon' => $menu->icon,
                'isGroup' => $menu->is_group,
                'sortOrder' => $menu->sort_order,
                'featureCode' => $menu->feature?->code,
                'children' => [],
            ];

            if ($menu->relationLoaded('children') && $menu->children->isNotEmpty()) {
                $node['children'] = $this->buildMenuTree($menu->children);
            }

            $tree[] = $node;
        }

        return $tree;
    }
}
