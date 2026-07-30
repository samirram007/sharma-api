<?php

namespace Modules\Vehicle\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Vehicle\Contracts\VehicleServiceInterface;
use Modules\Vehicle\Requests\VehicleRequest;
use Modules\Vehicle\Resources\VehicleCollection;
use Modules\Vehicle\Resources\VehicleResource;

class VehicleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected VehicleServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new VehicleCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new VehicleResource($data);
    }

    public function store(VehicleRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new VehicleResource($data, $messages = 'Vehicle created successfully');
    }

    public function update(VehicleRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new VehicleResource($data, $messages = 'Vehicle updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Vehicle');
    }
}
