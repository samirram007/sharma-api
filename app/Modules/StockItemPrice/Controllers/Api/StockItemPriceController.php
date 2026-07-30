<?php

namespace Modules\StockItemPrice\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItemPrice\Contracts\StockItemPriceServiceInterface;
use Modules\StockItemPrice\Requests\StockItemPriceRequest;
use Modules\StockItemPrice\Resources\StockItemPriceCollection;
use Modules\StockItemPrice\Resources\StockItemPriceResource;

class StockItemPriceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockItemPriceServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockItemPriceCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockItemPriceResource($data);
    }

    public function store(StockItemPriceRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockItemPriceResource($data, $messages = 'StockItemPrice created successfully');
    }

    public function update(StockItemPriceRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockItemPriceResource($data, $messages = 'StockItemPrice updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockItemPrice');
    }
}
