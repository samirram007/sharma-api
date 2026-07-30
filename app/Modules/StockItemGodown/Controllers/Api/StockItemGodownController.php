<?php

namespace Modules\StockItemGodown\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItemGodown\Contracts\StockItemGodownServiceInterface;
use Modules\StockItemGodown\Requests\StockItemGodownRequest;
use Modules\StockItemGodown\Resources\StockItemGodownCollection;
use Modules\StockItemGodown\Resources\StockItemGodownResource;

class StockItemGodownController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockItemGodownServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockItemGodownCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockItemGodownResource($data);
    }

    public function store(StockItemGodownRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockItemGodownResource($data, $messages = 'StockItemGodown created successfully');
    }

    public function update(StockItemGodownRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockItemGodownResource($data, $messages = 'StockItemGodown updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockItemGodown');
    }
}
