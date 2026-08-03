<?php

namespace Modules\StockItemPrice\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItemPrice\Facades\StockItemPriceFacade;
use Modules\StockItemPrice\Requests\StockItemPriceRequest;
use Modules\StockItemPrice\Resources\StockItemPriceCollection;
use Modules\StockItemPrice\Resources\StockItemPriceResource;

class StockItemPriceController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockItemPriceFacade::getAll();

        return new StockItemPriceCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockItemPriceFacade::getById($id);

        return new StockItemPriceResource($data);
    }

    public function store(StockItemPriceRequest $request): SuccessResource
    {
        $data = StockItemPriceFacade::store($request->validated());

        return new StockItemPriceResource($data, $messages = 'StockItemPrice created successfully');
    }

    public function update(StockItemPriceRequest $request, int $id): SuccessResource
    {
        $data = StockItemPriceFacade::update($request->validated(), $id);

        return new StockItemPriceResource($data, $messages = 'StockItemPrice updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockItemPriceFacade::delete($id), 'StockItemPrice');
    }
}
