<?php

namespace Modules\SalaryStructure\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\SalaryStructure\Facades\SalaryStructureFacade;
use Modules\SalaryStructure\Requests\SalaryStructureRequest;
use Modules\SalaryStructure\Resources\SalaryStructureCollection;
use Modules\SalaryStructure\Resources\SalaryStructureResource;

class SalaryStructureController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = SalaryStructureFacade::getAll();

        return new SalaryStructureCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = SalaryStructureFacade::getById($id);

        return new SalaryStructureResource($data);
    }

    public function store(SalaryStructureRequest $request): SuccessResource
    {
        $data = SalaryStructureFacade::store($request->validated());

        return new SalaryStructureResource($data, $messages = 'SalaryStructure created successfully');
    }

    public function update(SalaryStructureRequest $request, int $id): SuccessResource
    {
        $data = SalaryStructureFacade::update($request->validated(), $id);

        return new SalaryStructureResource($data, $messages = 'SalaryStructure updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(SalaryStructureFacade::delete($id), 'SalaryStructure');
    }
}
