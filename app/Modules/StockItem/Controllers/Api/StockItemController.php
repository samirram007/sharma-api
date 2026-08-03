<?php

namespace Modules\StockItem\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItem\Facades\StockItemFacade;
use Modules\StockItem\Requests\StockItemRequest;
use Modules\StockItem\Resources\StockItemCollection;
use Modules\StockItem\Resources\StockItemResource;

class StockItemController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockItemFacade::getAll();

        return new StockItemCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockItemFacade::getById($id);

        return new StockItemResource($data);
    }

    public function store(StockItemRequest $request): SuccessResource
    {
        $data = StockItemFacade::store($request->validated());

        return new StockItemResource($data, $messages = 'StockItem created successfully');
    }

    public function update(StockItemRequest $request, int $id): SuccessResource
    {
        $data = StockItemFacade::update($request->validated(), $id);

        return new StockItemResource($data, $messages = 'StockItem updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockItemFacade::delete($id), 'StockItem');
    }

    public function purchasable_stock_items(): SuccessCollection
    {
        $data = StockItemFacade::getPurchasableStockItems();

        return new StockItemCollection($data);
    }
}
