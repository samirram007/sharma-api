<?php

namespace Modules\Salary\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Salary\Contracts\SalaryServiceInterface;
use Modules\Salary\Requests\SalaryRequest;
use Modules\Salary\Resources\SalaryCollection;
use Modules\Salary\Resources\SalaryResource;

class SalaryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected SalaryServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new SalaryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new SalaryResource($data);
    }

    public function store(SalaryRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new SalaryResource($data, $messages = 'Salary created successfully');
    }

    public function update(SalaryRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new SalaryResource($data, $messages = 'Salary updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Salary');
    }
}
