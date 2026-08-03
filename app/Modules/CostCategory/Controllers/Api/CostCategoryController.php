<?php

namespace Modules\CostCategory\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\CostCategory\Facades\CostCategoryFacade;
use Modules\CostCategory\Requests\CostCategoryRequest;
use Modules\CostCategory\Resources\CostCategoryCollection;
use Modules\CostCategory\Resources\CostCategoryResource;

class CostCategoryController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = CostCategoryFacade::getAll();

        return new CostCategoryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = CostCategoryFacade::getById($id);

        return new CostCategoryResource($data);
    }

    public function store(CostCategoryRequest $request): SuccessResource
    {
        $data = CostCategoryFacade::store($request->validated());

        return new CostCategoryResource($data, $messages = 'CostCategory created successfully');
    }

    public function update(CostCategoryRequest $request, int $id): SuccessResource
    {
        $data = CostCategoryFacade::update($request->validated(), $id);

        return new CostCategoryResource($data, $messages = 'CostCategory updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(CostCategoryFacade::delete($id), 'CostCategory');
    }
}
