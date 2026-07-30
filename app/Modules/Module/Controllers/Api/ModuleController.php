<?php

namespace Modules\Module\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Module\Contracts\ModuleServiceInterface;
use Modules\Module\Requests\ModuleRequest;
use Modules\Module\Resources\ModuleCollection;
use Modules\Module\Resources\ModuleResource;

class ModuleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ModuleServiceInterface $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();

        return (new ModuleCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getById($id);

        return $this->resourceResponse(
            new ModuleResource($data),
            'Module retrieved successfully'
        );
    }

    public function store(ModuleRequest $request): JsonResponse
    {
        $data = $this->service->store($request->validated());

        return $this->resourceResponse(
            new ModuleResource($data),
            'Module created successfully',
            201
        );
    }

    public function update(ModuleRequest $request, int $id): JsonResponse
    {
        $data = $this->service->update($request->validated(), $id);

        return $this->resourceResponse(
            new ModuleResource($data),
            'Module updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Module');
    }
}
