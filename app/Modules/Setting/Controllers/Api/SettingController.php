<?php

namespace Modules\Setting\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Facades\SettingFacade;
use Modules\Setting\Requests\SettingRequest;
use Modules\Setting\Resources\SettingCollection;
use Modules\Setting\Resources\SettingResource;

class SettingController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = SettingFacade::getAll();

        return (new SettingCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = SettingFacade::getById($id);

        return $this->resourceResponse(
            new SettingResource($data),
            'Setting retrieved successfully'
        );
    }

    public function store(SettingRequest $request): JsonResponse
    {
        $data = SettingFacade::store($request->validated());

        return $this->resourceResponse(
            new SettingResource($data),
            'Setting created successfully',
            201
        );
    }

    public function update(SettingRequest $request, int $id): JsonResponse
    {
        $data = SettingFacade::update($request->validated(), $id);

        return $this->resourceResponse(
            new SettingResource($data),
            'Setting updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(SettingFacade::delete($id), 'Setting');
    }
}
