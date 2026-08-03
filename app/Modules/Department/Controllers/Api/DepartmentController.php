<?php

namespace Modules\Department\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Department\Facades\DepartmentFacade;
use Modules\Department\Requests\DepartmentRequest;
use Modules\Department\Resources\DepartmentCollection;
use Modules\Department\Resources\DepartmentResource;

class DepartmentController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = DepartmentFacade::getAll();

        return new DepartmentCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = DepartmentFacade::getById($id);

        return new DepartmentResource($data);
    }

    public function store(DepartmentRequest $request): SuccessResource
    {
        $data = DepartmentFacade::store($request->validated());

        return new DepartmentResource($data, $messages = 'Department created successfully');
    }

    public function update(DepartmentRequest $request, int $id): SuccessResource
    {
        $data = DepartmentFacade::update($request->validated(), $id);

        return new DepartmentResource($data, $messages = 'Department updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(DepartmentFacade::delete($id), 'Department');
    }
}
