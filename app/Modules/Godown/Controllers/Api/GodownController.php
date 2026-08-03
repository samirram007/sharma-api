<?php

namespace Modules\Godown\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Godown\Facades\GodownFacade;
use Modules\Godown\Requests\GodownRequest;
use Modules\Godown\Resources\GodownCollection;
use Modules\Godown\Resources\GodownResource;

class GodownController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = GodownFacade::getAll();

        return new GodownCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = GodownFacade::getById($id);

        return new GodownResource($data);
    }

    public function store(GodownRequest $request): SuccessResource
    {
        $data = GodownFacade::store($request->validated());

        return new GodownResource($data, $messages = 'Godown created successfully');
    }

    public function update(GodownRequest $request, int $id): SuccessResource
    {
        $data = GodownFacade::update($request->validated(), $id);

        return new GodownResource($data, $messages = 'Godown updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(GodownFacade::delete($id), 'Godown');
    }

    public function godown_item_stocks(int $item_id): SuccessCollection
    {
        $data = GodownFacade::getGodownItemStocks($item_id);

        return new SuccessCollection($data);
    }

    public function godown_item_batches(int $item_id, int $godown_id): SuccessCollection
    {
        $data = GodownFacade::getGodownItemBatches($item_id, $godown_id);

        return new SuccessCollection($data);
    }

    public function zones(): SuccessCollection
    {
        $data = GodownFacade::getZones();

        return new GodownCollection($data);
    }

    public function zonesById(int $id): SuccessResource
    {
        $data = GodownFacade::getZonesById($id);

        return new SuccessResource($data);
    }
}
