<?php

namespace Modules\Setting\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Contracts\SettingServiceInterface;
use Modules\Setting\Requests\SettingRequest;
use Modules\Setting\Resources\SettingCollection;
use Modules\Setting\Resources\SettingResource;

class SettingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected SettingServiceInterface $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();

        return (new SettingCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getById($id);

        return $this->resourceResponse(
            new SettingResource($data),
            'Setting retrieved successfully'
        );
    }

    public function store(SettingRequest $request): JsonResponse
    {
        $data = $this->service->store($request->validated());

        return $this->resourceResponse(
            new SettingResource($data),
            'Setting created successfully',
            201
        );
    }

    public function update(SettingRequest $request, int $id): JsonResponse
    {
        $data = $this->service->update($request->validated(), $id);

        return $this->resourceResponse(
            new SettingResource($data),
            'Setting updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Setting');
    }
}
