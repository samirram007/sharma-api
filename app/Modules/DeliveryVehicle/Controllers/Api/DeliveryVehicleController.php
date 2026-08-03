<?php

namespace Modules\DeliveryVehicle\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\DeliveryVehicle\Facades\DeliveryVehicleFacade;
use Modules\DeliveryVehicle\Requests\DeliveryVehicleRequest;
use Modules\DeliveryVehicle\Resources\DeliveryVehicleCollection;
use Modules\DeliveryVehicle\Resources\DeliveryVehicleResource;

class DeliveryVehicleController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = DeliveryVehicleFacade::getAll();

        return new DeliveryVehicleCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = DeliveryVehicleFacade::getById($id);

        return new DeliveryVehicleResource($data);
    }

    public function store(DeliveryVehicleRequest $request): SuccessResource
    {
        $data = DeliveryVehicleFacade::store($request->validated());

        return new DeliveryVehicleResource($data, $messages = 'DeliveryVehicle created successfully');
    }

    public function update(DeliveryVehicleRequest $request, int $id): SuccessResource
    {
        $data = DeliveryVehicleFacade::update($request->validated(), $id);

        return new DeliveryVehicleResource($data, $messages = 'DeliveryVehicle updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(DeliveryVehicleFacade::delete($id), 'DeliveryVehicle');
    }
}
