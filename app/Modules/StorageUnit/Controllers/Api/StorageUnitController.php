<?php

namespace Modules\StorageUnit\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StorageUnit\Facades\StorageUnitFacade;
use Modules\StorageUnit\Requests\StorageUnitRequest;
use Modules\StorageUnit\Resources\StorageUnitCollection;
use Modules\StorageUnit\Resources\StorageUnitResource;

class StorageUnitController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StorageUnitFacade::getAll();

        return new StorageUnitCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StorageUnitFacade::getById($id);

        return new StorageUnitResource($data);
    }

    public function store(StorageUnitRequest $request): SuccessResource
    {
        $data = StorageUnitFacade::store($request->validated());

        return new StorageUnitResource($data, $messages = 'StorageUnit created successfully');
    }

    public function update(StorageUnitRequest $request, int $id): SuccessResource
    {
        $data = StorageUnitFacade::update($request->validated(), $id);

        return new StorageUnitResource($data, $messages = 'StorageUnit updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StorageUnitFacade::delete($id), 'StorageUnit');
    }
}
