<?php

namespace Modules\SalaryComponent\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\SalaryComponent\Contracts\SalaryComponentServiceInterface;
use Modules\SalaryComponent\Requests\SalaryComponentRequest;
use Modules\SalaryComponent\Resources\SalaryComponentCollection;
use Modules\SalaryComponent\Resources\SalaryComponentResource;

class SalaryComponentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected SalaryComponentServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new SalaryComponentCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new SalaryComponentResource($data);
    }

    public function store(SalaryComponentRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new SalaryComponentResource($data, $messages = 'SalaryComponent created successfully');
    }

    public function update(SalaryComponentRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new SalaryComponentResource($data, $messages = 'SalaryComponent updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'SalaryComponent');
    }
}
