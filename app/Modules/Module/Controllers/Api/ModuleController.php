<?php

namespace Modules\Module\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Module\Facades\ModuleFacade;
use Modules\Module\Requests\ModuleRequest;
use Modules\Module\Resources\ModuleCollection;
use Modules\Module\Resources\ModuleResource;

class ModuleController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = ModuleFacade::getAll();

        return (new ModuleCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = ModuleFacade::getById($id);

        return $this->resourceResponse(
            new ModuleResource($data),
            'Module retrieved successfully'
        );
    }

    public function store(ModuleRequest $request): JsonResponse
    {
        $data = ModuleFacade::store($request->validated());

        return $this->resourceResponse(
            new ModuleResource($data),
            'Module created successfully',
            201
        );
    }

    public function update(ModuleRequest $request, int $id): JsonResponse
    {
        $data = ModuleFacade::update($request->validated(), $id);

        return $this->resourceResponse(
            new ModuleResource($data),
            'Module updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(ModuleFacade::delete($id), 'Module');
    }
}
