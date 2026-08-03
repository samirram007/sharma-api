<?php

namespace Modules\StockItemBatch\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItemBatch\Facades\StockItemBatchFacade;
use Modules\StockItemBatch\Requests\StockItemBatchRequest;
use Modules\StockItemBatch\Resources\StockItemBatchCollection;
use Modules\StockItemBatch\Resources\StockItemBatchResource;

class StockItemBatchController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockItemBatchFacade::getAll();

        return new StockItemBatchCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockItemBatchFacade::getById($id);

        return new StockItemBatchResource($data);
    }

    public function store(StockItemBatchRequest $request): SuccessResource
    {
        $data = StockItemBatchFacade::store($request->validated());

        return new StockItemBatchResource($data, $messages = 'StockItemBatch created successfully');
    }

    public function update(StockItemBatchRequest $request, int $id): SuccessResource
    {
        $data = StockItemBatchFacade::update($request->validated(), $id);

        return new StockItemBatchResource($data, $messages = 'StockItemBatch updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockItemBatchFacade::delete($id), 'StockItemBatch');
    }
}
