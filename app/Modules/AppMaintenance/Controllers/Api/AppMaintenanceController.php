<?php

namespace Modules\AppMaintenance\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AppMaintenance\Facades\AppMaintenanceFacade;
use Modules\AppMaintenance\Requests\AppMaintenanceRequest;
use Modules\AppMaintenance\Resources\AppMaintenanceCollection;
use Modules\AppMaintenance\Resources\AppMaintenanceResource;

class AppMaintenanceController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = AppMaintenanceFacade::getAll();

        return new AppMaintenanceCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = AppMaintenanceFacade::getById($id);

        return new AppMaintenanceResource($data);
    }

    public function store(AppMaintenanceRequest $request): SuccessResource
    {
        $data = AppMaintenanceFacade::store($request->validated());

        return new AppMaintenanceResource($data, $messages = 'AppMaintenance created successfully');
    }

    public function update(AppMaintenanceRequest $request, int $id): SuccessResource
    {
        $data = AppMaintenanceFacade::update($request->validated(), $id);

        return new AppMaintenanceResource($data, $messages = 'AppMaintenance updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = AppMaintenanceFacade::delete($id);

        return $this->deletedResponse($result, 'AppMaintenance');
    }
}
