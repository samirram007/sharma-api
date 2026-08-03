<?php

namespace Modules\SalaryComponent\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\SalaryComponent\Facades\SalaryComponentFacade;
use Modules\SalaryComponent\Requests\SalaryComponentRequest;
use Modules\SalaryComponent\Resources\SalaryComponentCollection;
use Modules\SalaryComponent\Resources\SalaryComponentResource;

class SalaryComponentController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = SalaryComponentFacade::getAll();

        return new SalaryComponentCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = SalaryComponentFacade::getById($id);

        return new SalaryComponentResource($data);
    }

    public function store(SalaryComponentRequest $request): SuccessResource
    {
        $data = SalaryComponentFacade::store($request->validated());

        return new SalaryComponentResource($data, $messages = 'SalaryComponent created successfully');
    }

    public function update(SalaryComponentRequest $request, int $id): SuccessResource
    {
        $data = SalaryComponentFacade::update($request->validated(), $id);

        return new SalaryComponentResource($data, $messages = 'SalaryComponent updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(SalaryComponentFacade::delete($id), 'SalaryComponent');
    }
}
