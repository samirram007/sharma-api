<?php

namespace Modules\DeliveryPlace\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\DeliveryPlace\Contracts\DeliveryPlaceServiceInterface;
use Modules\DeliveryPlace\Requests\DeliveryPlaceRequest;
use Modules\DeliveryPlace\Resources\DeliveryPlaceCollection;
use Modules\DeliveryPlace\Resources\DeliveryPlaceResource;

class DeliveryPlaceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DeliveryPlaceServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new DeliveryPlaceCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DeliveryPlaceResource($data);
    }

    public function store(DeliveryPlaceRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DeliveryPlaceResource($data, $messages = 'DeliveryPlace created successfully');
    }

    public function update(DeliveryPlaceRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new DeliveryPlaceResource($data, $messages = 'DeliveryPlace updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'DeliveryPlace');
    }
}
