<?php

namespace Modules\Department\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Department\Contracts\DepartmentServiceInterface;
use Modules\Department\Requests\DepartmentRequest;
use Modules\Department\Resources\DepartmentCollection;
use Modules\Department\Resources\DepartmentResource;

class DepartmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DepartmentServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new DepartmentCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DepartmentResource($data);
    }

    public function store(DepartmentRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DepartmentResource($data, $messages = 'Department created successfully');
    }

    public function update(DepartmentRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new DepartmentResource($data, $messages = 'Department updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Department');
    }
}
