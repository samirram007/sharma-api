<?php

namespace Modules\Role\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Role\Facades\RoleFacade;
use Modules\Role\Requests\RoleRequest;
use Modules\Role\Resources\RoleCollection;
use Modules\Role\Resources\RoleResource;

class RoleController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = RoleFacade::getAll();

        return new RoleCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = RoleFacade::getById($id);

        return new RoleResource($data);
    }

    public function store(RoleRequest $request): SuccessResource
    {
        $data = RoleFacade::store($request->validated());

        return new RoleResource($data, $messages = 'Role created successfully');
    }

    public function update(RoleRequest $request, int $id): SuccessResource
    {
        $data = RoleFacade::update($request->validated(), $id);

        return new RoleResource($data, $messages = 'Role updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(RoleFacade::delete($id), 'Role');
    }
}
