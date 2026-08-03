<?php

namespace Modules\Employee\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Employee\Facades\EmployeeFacade;
use Modules\Employee\Requests\EmployeeRequest;
use Modules\Employee\Resources\EmployeeCollection;
use Modules\Employee\Resources\EmployeeResource;

class EmployeeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = EmployeeFacade::getAll();

        return new EmployeeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = EmployeeFacade::getById($id);

        return new EmployeeResource($data);
    }

    public function store(EmployeeRequest $request): SuccessResource
    {
        $data = EmployeeFacade::store($request->validated());

        return new EmployeeResource($data, $messages = 'Employee created successfully');
    }

    public function update(EmployeeRequest $request, int $id): SuccessResource
    {
        $data = EmployeeFacade::update($request->validated(), $id);

        return new EmployeeResource($data, $messages = 'Employee updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(EmployeeFacade::delete($id), 'Employee');
    }
}
