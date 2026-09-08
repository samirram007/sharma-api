<?php

namespace Modules\Setting\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
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

    public function show(int $id): SuccessResource
    {
        $data = SettingFacade::getById($id);

        return new SettingResource($data, 'Setting retrieved successfully');
    }

    public function store(SettingRequest $request): SuccessResource
    {
        $data = SettingFacade::store($request->validated());

        return new SettingResource($data, 'Setting created successfully');
    }

    public function update(SettingRequest $request, int $id): SuccessResource
    {
        $data = SettingFacade::update($request->validated(), $id);

        return new SettingResource($data, 'Setting updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(SettingFacade::delete($id), 'Setting');
    }
}
