<?php

namespace Modules\StockUnit\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockUnit\Facades\StockUnitFacade;
use Modules\StockUnit\Requests\StockUnitRequest;
use Modules\StockUnit\Resources\StockUnitCollection;
use Modules\StockUnit\Resources\StockUnitResource;

class StockUnitController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockUnitFacade::getAll();

        return new StockUnitCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockUnitFacade::getById($id);

        return new StockUnitResource($data);
    }

    public function store(StockUnitRequest $request): SuccessResource
    {
        $data = StockUnitFacade::store($request->validated());

        return new StockUnitResource($data, $messages = 'StockUnit created successfully');
    }

    public function update(StockUnitRequest $request, int $id): SuccessResource
    {
        $data = StockUnitFacade::update($request->validated(), $id);

        return new StockUnitResource($data, $messages = 'StockUnit updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockUnitFacade::delete($id), 'StockUnit');
    }
}
