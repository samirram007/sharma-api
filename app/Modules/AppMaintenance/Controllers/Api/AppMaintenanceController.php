<?php

namespace App\Modules\AppMaintenance\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\AppMaintenance\Contracts\AppMaintenanceServiceInterface;
use App\Modules\AppMaintenance\Resources\AppMaintenanceResource;
use App\Modules\AppMaintenance\Resources\AppMaintenanceCollection;
use App\Modules\AppMaintenance\Requests\AppMaintenanceRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class AppMaintenanceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AppMaintenanceServiceInterface $service)
    {
    }

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();
        return new AppMaintenanceCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return new AppMaintenanceResource($data);
    }

    public function store(AppMaintenanceRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
        return new AppMaintenanceResource($data, $messages = 'AppMaintenance created successfully');
    }

    public function update(AppMaintenanceRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return new AppMaintenanceResource($data, $messages = 'AppMaintenance updated successfully');
    }

    public function destroy(int $id): SuccessResource
    {
        $this->service->delete($id);
        return new AppMaintenanceResource(null, $messages = 'AppMaintenance updated successfully');
    }
}
