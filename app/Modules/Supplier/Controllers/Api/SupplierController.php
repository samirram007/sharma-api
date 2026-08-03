<?php

namespace Modules\Supplier\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Supplier\Facades\SupplierFacade;
use Modules\Supplier\Requests\SupplierRequest;
use Modules\Supplier\Resources\SupplierCollection;
use Modules\Supplier\Resources\SupplierResource;

class SupplierController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = SupplierFacade::getAll();

        return new SupplierCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = SupplierFacade::getById($id);

        return new SupplierResource($data);
    }

    public function store(SupplierRequest $request): SuccessResource
    {

        $data = SupplierFacade::store($request->validated());

        return new SupplierResource($data, $messages = 'Supplier created successfully');
    }

    public function update(SupplierRequest $request, int $id): SuccessResource
    {
        $data = SupplierFacade::update($request->validated(), $id);

        return new SupplierResource($data, $messages = 'Supplier updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(SupplierFacade::delete($id), 'Supplier');
    }
}
