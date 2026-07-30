<?php

namespace Modules\UserRole\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\UserRole\Contracts\UserRoleServiceInterface;
use Modules\UserRole\Requests\UserRoleRequest;
use Modules\UserRole\Resources\UserRoleCollection;
use Modules\UserRole\Resources\UserRoleResource;

class UserRoleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected UserRoleServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new UserRoleCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new UserRoleResource($data);
    }

    public function store(UserRoleRequest $request): SuccessResource|JsonResponse
    {
        $data = $this->service->store($request->validated());

        if ($data) {
            return new UserRoleResource($data ?? [], $messages = 'Role assigned successfully');
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Role unassigned successfully',
        ]);

    }

    public function update(UserRoleRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new UserRoleResource($data, $messages = 'UserRole updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'UserRole');
    }
}
