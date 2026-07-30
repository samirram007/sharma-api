<?php

namespace Modules\DeliveryRoute\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\DeliveryRoute\Contracts\DeliveryRouteServiceInterface;
use Modules\DeliveryRoute\Requests\DeliveryRouteRequest;
use Modules\DeliveryRoute\Resources\DeliveryRouteCollection;
use Modules\DeliveryRoute\Resources\DeliveryRouteResource;

class DeliveryRouteController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DeliveryRouteServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new DeliveryRouteCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DeliveryRouteResource($data);
    }

    public function store(DeliveryRouteRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DeliveryRouteResource($data, $messages = 'DeliveryRoute created successfully');
    }

    public function update(DeliveryRouteRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new DeliveryRouteResource($data, $messages = 'DeliveryRoute updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'DeliveryRoute');
    }
}
