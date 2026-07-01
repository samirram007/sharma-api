<?php

namespace Modules\Menu\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use Modules\Menu\Contracts\MenuServiceInterface;
use Modules\Menu\Resources\MenuResource;
use Modules\Menu\Resources\MenuCollection;
use Modules\Menu\Resources\MenuResourceCollection;
use Modules\Menu\Requests\MenuRequest;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected MenuServiceInterface $service)
    {
    }

    public function index(): SuccessCollection
    {

        $data = $this->service->getAll();
       // dd($data);
        return new MenuCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return new MenuResource($data);
    }

    public function store(MenuRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
        return new MenuResource($data, 'Menu entry created successfully');
    }

    public function update(MenuRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return new MenuResource($data, 'Menu entry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result ? 'Menu entry deleted successfully' : 'Menu entry not found',
        ]);
    }

    /** Get hierarchical tree of all menu entries for management UI. */
    public function tree(): SuccessCollection
    {
        $data = $this->service->getTree();
        return new MenuCollection($data);
    }

    /**
     * Batch reorder menu items (drag & drop).
     * Expects JSON body: { items: [{ id, sort_order, parent_id? }] }
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.id'        => 'required|integer|exists:menu,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|integer|exists:menu,id',
        ]);

        $this->service->reorder($request->input('items'));

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Menu reordered successfully.',
        ]);
    }

    /**
     * Get allowed feature/permission codes for the authenticated user.
     * Used by the frontend to gate feature access.
     * Route: GET /user/menu
     */
    public function userMenu(): JsonResponse
    {
        $permissions = $this->service->getUserMenuPermissions();

        return response()->json([
            'status' => 'success',
            'data'   => $permissions,
        ]);
    }

    /**
     * Get hierarchical menu tree filtered by the authenticated user's role permissions.
     * Used by the frontend sidebar to render the dynamic navigation.
     * Route: GET /auth/menus
     */
    public function userMenuTree(): JsonResponse
    {
        $tree = $this->service->getUserMenuTree();

        return response()->json([
            'status' => 'success',
            'data'   => $tree,
        ]);
    }

    /**
     * Get all menu features with their permission status for a given role.
     * Used by the Menu Manager UI.
     * Route: GET /role/{role_id}/menu-permissions
     */
    public function roleMenuPermissions(int $roleId): SuccessCollection
    {
        $data = $this->service->getRoleMenuPermissions($roleId);
        return new \Modules\AppModuleFeature\Resources\AppModuleFeatureCollection($data);
    }
}
