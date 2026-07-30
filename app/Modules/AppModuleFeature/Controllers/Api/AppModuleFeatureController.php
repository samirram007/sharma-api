<?php

namespace Modules\AppModuleFeature\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AppModuleFeature\Contracts\AppModuleFeatureServiceInterface;
use Modules\AppModuleFeature\Requests\AppModuleFeatureRequest;
use Modules\AppModuleFeature\Resources\AppModuleFeatureCollection;
use Modules\AppModuleFeature\Resources\AppModuleFeatureResource;

class AppModuleFeatureController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AppModuleFeatureServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new AppModuleFeatureCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new AppModuleFeatureResource($data);
    }

    public function store(AppModuleFeatureRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new AppModuleFeatureResource($data, $messages = 'AppModuleFeature created successfully');
    }

    public function update(AppModuleFeatureRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new AppModuleFeatureResource($data, $messages = 'AppModuleFeature updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'AppModuleFeature');
    }

    public function getModuleFeaturesByRole(int $role_id, int $module_id): SuccessCollection
    {
        $data = $this->service->getByRoleAndModule($role_id, $module_id);

        // dd($data);
        return new AppModuleFeatureCollection($data);
    }
}
