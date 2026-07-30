<?php

namespace Modules\RolePermission\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\RolePermission\Contracts\RolePermissionServiceInterface;
use Modules\RolePermission\Requests\RolePermissionRequest;
use Modules\RolePermission\Resources\RolePermissionCollection;
use Modules\RolePermission\Resources\RolePermissionResource;

class RolePermissionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected RolePermissionServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new RolePermissionCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new RolePermissionResource($data);
    }

    public function store(RolePermissionRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new RolePermissionResource($data, $messages = 'RolePermission created successfully');
    }

    public function update(RolePermissionRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new RolePermissionResource($data, $messages = 'RolePermission updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'RolePermission');
    }
}
