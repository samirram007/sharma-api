<?php

namespace Modules\StockGroup\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockGroup\Facades\StockGroupFacade;
use Modules\StockGroup\Requests\StockGroupRequest;
use Modules\StockGroup\Resources\StockGroupCollection;
use Modules\StockGroup\Resources\StockGroupResource;

class StockGroupController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockGroupFacade::getAll();

        return new StockGroupCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockGroupFacade::getById($id);

        return new StockGroupResource($data);
    }

    public function store(StockGroupRequest $request): SuccessResource
    {
        $data = StockGroupFacade::store($request->validated());

        return new StockGroupResource($data, $messages = 'StockGroup created successfully');

    }

    public function update(StockGroupRequest $request, int $id): SuccessResource
    {
        $data = StockGroupFacade::update($request->validated(), $id);

        return new StockGroupResource($data, $messages = 'StockGroup updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockGroupFacade::delete($id), 'StockGroup');
    }
}
