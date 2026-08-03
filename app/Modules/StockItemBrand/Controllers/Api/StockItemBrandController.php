<?php

namespace Modules\StockItemBrand\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItemBrand\Facades\StockItemBrandFacade;
use Modules\StockItemBrand\Requests\StockItemBrandRequest;
use Modules\StockItemBrand\Resources\StockItemBrandCollection;
use Modules\StockItemBrand\Resources\StockItemBrandResource;

class StockItemBrandController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockItemBrandFacade::getAll();

        return new StockItemBrandCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockItemBrandFacade::getById($id);

        return new StockItemBrandResource($data);
    }

    public function store(StockItemBrandRequest $request): SuccessResource
    {
        $data = StockItemBrandFacade::store($request->validated());

        return new StockItemBrandResource($data, $messages = 'StockItemBrand created successfully');
    }

    public function update(StockItemBrandRequest $request, int $id): SuccessResource
    {
        $data = StockItemBrandFacade::update($request->validated(), $id);

        return new StockItemBrandResource($data, $messages = 'StockItemBrand updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockItemBrandFacade::delete($id), 'StockItemBrand');
    }
}
