<?php

namespace Modules\StockItemSerial\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItemSerial\Contracts\StockItemSerialServiceInterface;
use Modules\StockItemSerial\Requests\StockItemSerialRequest;
use Modules\StockItemSerial\Resources\StockItemSerialCollection;
use Modules\StockItemSerial\Resources\StockItemSerialResource;

class StockItemSerialController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockItemSerialServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockItemSerialCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockItemSerialResource($data);
    }

    public function store(StockItemSerialRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockItemSerialResource($data, $messages = 'StockItemSerial created successfully');
    }

    public function update(StockItemSerialRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockItemSerialResource($data, $messages = 'StockItemSerial updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockItemSerial');
    }
}
