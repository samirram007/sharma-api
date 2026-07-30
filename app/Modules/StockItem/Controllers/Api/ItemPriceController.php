<?php

namespace Modules\StockItem\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItem\Contracts\StockItemServiceInterface;
use Modules\StockItem\Requests\StockItemRequest;
use Modules\StockItem\Resources\StockItemCollection;
use Modules\StockItem\Resources\StockItemResource;

class ItemPriceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockItemServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockItemCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockItemResource($data);
    }

    public function store(StockItemRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockItemResource($data, $messages = 'StockItem created successfully');
    }

    public function update(StockItemRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockItemResource($data, $messages = 'StockItem updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockItem');
    }
}
