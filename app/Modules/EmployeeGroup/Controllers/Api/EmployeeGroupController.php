<?php

namespace Modules\EmployeeGroup\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\EmployeeGroup\Contracts\EmployeeGroupServiceInterface;
use Modules\EmployeeGroup\Requests\EmployeeGroupRequest;
use Modules\EmployeeGroup\Resources\EmployeeGroupCollection;
use Modules\EmployeeGroup\Resources\EmployeeGroupResource;

class EmployeeGroupController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected EmployeeGroupServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new EmployeeGroupCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new EmployeeGroupResource($data);
    }

    public function store(EmployeeGroupRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new EmployeeGroupResource($data, $messages = 'EmployeeGroup created successfully');
    }

    public function update(EmployeeGroupRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new EmployeeGroupResource($data, $messages = 'EmployeeGroup updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'EmployeeGroup');
    }
}
