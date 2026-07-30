<?php

namespace Modules\Supplier\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Supplier\Contracts\SupplierServiceInterface;
use Modules\Supplier\Requests\SupplierRequest;
use Modules\Supplier\Resources\SupplierCollection;
use Modules\Supplier\Resources\SupplierResource;

class SupplierController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected SupplierServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new SupplierCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new SupplierResource($data);
    }

    public function store(SupplierRequest $request): SuccessResource
    {

        $data = $this->service->store($request->validated());

        return new SupplierResource($data, $messages = 'Supplier created successfully');
    }

    public function update(SupplierRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new SupplierResource($data, $messages = 'Supplier updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Supplier');
    }
}
