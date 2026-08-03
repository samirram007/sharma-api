<?php

namespace Modules\AppModule\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AppModule\Facades\AppModuleFacade;
use Modules\AppModule\Requests\AppModuleRequest;
use Modules\AppModule\Resources\AppModuleCollection;
use Modules\AppModule\Resources\AppModuleResource;

class AppModuleController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = AppModuleFacade::getAll();

        return new AppModuleCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = AppModuleFacade::getById($id);

        return new AppModuleResource($data);
    }

    public function store(AppModuleRequest $request): SuccessResource
    {
        $data = AppModuleFacade::store($request->validated());

        return new AppModuleResource($data, $messages = 'AppModule created successfully');
    }

    public function update(AppModuleRequest $request, int $id): SuccessResource
    {
        $data = AppModuleFacade::update($request->validated(), $id);

        return new AppModuleResource($data, $messages = 'AppModule updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(AppModuleFacade::delete($id), 'AppModule');
    }
}
