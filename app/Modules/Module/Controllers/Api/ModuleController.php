<?php

namespace Modules\Module\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
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

    public function show(int $id): SuccessResource
    {
        $data = ModuleFacade::getById($id);

        return new ModuleResource($data, 'Module retrieved successfully');
    }

    public function store(ModuleRequest $request): SuccessResource
    {
        $data = ModuleFacade::store($request->validated());

        return new ModuleResource($data, 'Module created successfully');
    }

    public function update(ModuleRequest $request, int $id): SuccessResource
    {
        $data = ModuleFacade::update($request->validated(), $id);

        return new ModuleResource($data, 'Module updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(ModuleFacade::delete($id), 'Module');
    }
}
