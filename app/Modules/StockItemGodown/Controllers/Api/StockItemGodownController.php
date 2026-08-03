<?php

namespace Modules\StockItemGodown\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockItemGodown\Facades\StockItemGodownFacade;
use Modules\StockItemGodown\Requests\StockItemGodownRequest;
use Modules\StockItemGodown\Resources\StockItemGodownCollection;
use Modules\StockItemGodown\Resources\StockItemGodownResource;

class StockItemGodownController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockItemGodownFacade::getAll();

        return new StockItemGodownCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockItemGodownFacade::getById($id);

        return new StockItemGodownResource($data);
    }

    public function store(StockItemGodownRequest $request): SuccessResource
    {
        $data = StockItemGodownFacade::store($request->validated());

        return new StockItemGodownResource($data, $messages = 'StockItemGodown created successfully');
    }

    public function update(StockItemGodownRequest $request, int $id): SuccessResource
    {
        $data = StockItemGodownFacade::update($request->validated(), $id);

        return new StockItemGodownResource($data, $messages = 'StockItemGodown updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockItemGodownFacade::delete($id), 'StockItemGodown');
    }
}
