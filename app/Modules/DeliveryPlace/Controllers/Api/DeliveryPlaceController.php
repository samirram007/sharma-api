<?php

namespace Modules\DeliveryPlace\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\DeliveryPlace\Facades\DeliveryPlaceFacade;
use Modules\DeliveryPlace\Requests\DeliveryPlaceRequest;
use Modules\DeliveryPlace\Resources\DeliveryPlaceCollection;
use Modules\DeliveryPlace\Resources\DeliveryPlaceResource;

class DeliveryPlaceController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = DeliveryPlaceFacade::getAll();

        return new DeliveryPlaceCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = DeliveryPlaceFacade::getById($id);

        return new DeliveryPlaceResource($data);
    }

    public function store(DeliveryPlaceRequest $request): SuccessResource
    {
        $data = DeliveryPlaceFacade::store($request->validated());

        return new DeliveryPlaceResource($data, $messages = 'DeliveryPlace created successfully');
    }

    public function update(DeliveryPlaceRequest $request, int $id): SuccessResource
    {
        $data = DeliveryPlaceFacade::update($request->validated(), $id);

        return new DeliveryPlaceResource($data, $messages = 'DeliveryPlace updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(DeliveryPlaceFacade::delete($id), 'DeliveryPlace');
    }
}
