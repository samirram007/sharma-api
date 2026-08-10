<?php

namespace Modules\Menu\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AppModuleFeature\Resources\AppModuleFeatureCollection;
use Modules\Menu\Facades\MenuFacade;
use Modules\Menu\Requests\MenuRequest;
use Modules\Menu\Resources\MenuCollection;
use Modules\Menu\Resources\MenuResource;

class MenuController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = MenuFacade::getAll();

        return new MenuCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = MenuFacade::getById($id);

        return new MenuResource($data);
    }

    public function store(MenuRequest $request): SuccessResource
    {
        $data = MenuFacade::store($request->validated());

        return new MenuResource($data, 'Menu entry created successfully');
    }

    public function update(MenuRequest $request, int $id): SuccessResource
    {
        $data = MenuFacade::update($request->validated(), $id);

        return new MenuResource($data, 'Menu entry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(MenuFacade::delete($id), 'Menu entry');
    }

    /** Get hierarchical tree of all menu entries for management UI. */
    public function tree(): SuccessCollection
    {
        $data = MenuFacade::getTree();

        return new MenuCollection($data);
    }

    /**
     * Batch reorder menu items (drag & drop).
     * Expects JSON body: { items: [{ id, sort_order, parent_id? }] }
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:menu,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|integer|exists:menu,id',
        ]);

        MenuFacade::reorder($request->input('items'));

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Menu reordered successfully.',
        ]);
    }

    /**
     * Batch update menu items — toggle visibility, status, etc.
     * Expects JSON body: { ids: number[], data: { field: string, value: mixed } }
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:menu,id',
            'data' => 'required|array',
        ]);

        MenuFacade::batchUpdate($request->input('ids'), $request->input('data'));

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => count($request->input('ids')).' menu items updated successfully.',
        ]);
    }

    /**
     * Batch delete menu items.
     * Expects JSON body: { ids: number[] }
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:menu,id',
        ]);

        $deleted = MenuFacade::batchDelete($request->input('ids'));

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => $deleted.' menu items deleted successfully.',
        ]);
    }

    /**
     * Duplicate a menu entry and all its children.
     */
    public function duplicate(int $id): SuccessResource
    {
        $data = MenuFacade::duplicate($id);

        return new MenuResource($data, 'Menu entry duplicated successfully');
    }

    /**
     * Export all menu entries as JSON.
     */
    public function export(): JsonResponse
    {
        $data = MenuFacade::exportJson();

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
            'message' => 'Menu entries exported successfully.',
        ]);
    }

    /**
     * Import menu entries from JSON.
     * Expects JSON body: { items: array }
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        $count = MenuFacade::importJson($request->input('items'));

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => $count.' menu entries imported successfully.',
        ]);
    }

    /**
     * Search menu entries with pagination.
     * Query params: ?search=term&per_page=20
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:1|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $results = MenuFacade::search(
            $request->input('search'),
            $request->input('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => MenuResource::collection($results->items()),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    /**
     * Get allowed feature/permission codes for the authenticated user.
     * Used by the frontend to gate feature access.
     * Route: GET /user/menu
     */
    public function userMenu(): JsonResponse
    {
        $permissions = MenuFacade::getUserMenuPermissions();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Get hierarchical menu tree filtered by the authenticated user's role permissions.
     * Used by the frontend sidebar to render the dynamic navigation.
     * Route: GET /auth/menus
     */
    public function userMenuTree(): JsonResponse
    {
        $tree = MenuFacade::getUserMenuTree();

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    /**
     * Get hierarchical top-navigation menu tree (is_top_menu = true)
     * filtered by the authenticated user's role permissions.
     * Used by the frontend header to render the top navigation.
     * Route: GET /auth/top_menus
     */
    public function userTopMenuTree(): JsonResponse
    {
        $tree = MenuFacade::getTopMenuTree();

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    /**
     * Get all menu features with their permission status for a given role.
     * Used by the Menu Manager UI.
     * Route: GET /role/{role_id}/menu-permissions
     */
    public function roleMenuPermissions(int $roleId): SuccessCollection
    {
        $data = MenuFacade::getRoleMenuPermissions($roleId);

        return new AppModuleFeatureCollection($data);
    }
}
