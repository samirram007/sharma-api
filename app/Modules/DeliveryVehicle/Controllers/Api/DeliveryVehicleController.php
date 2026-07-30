<?php

namespace Modules\DeliveryVehicle\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\DeliveryVehicle\Contracts\DeliveryVehicleServiceInterface;
use Modules\DeliveryVehicle\Requests\DeliveryVehicleRequest;
use Modules\DeliveryVehicle\Resources\DeliveryVehicleCollection;
use Modules\DeliveryVehicle\Resources\DeliveryVehicleResource;

class DeliveryVehicleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DeliveryVehicleServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        // dd('called');
        $data = $this->service->getAll();

        return new DeliveryVehicleCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DeliveryVehicleResource($data);
    }

    public function store(DeliveryVehicleRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DeliveryVehicleResource($data, $messages = 'DeliveryVehicle created successfully');
    }

    public function update(DeliveryVehicleRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new DeliveryVehicleResource($data, $messages = 'DeliveryVehicle updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'DeliveryVehicle');
    }
}
