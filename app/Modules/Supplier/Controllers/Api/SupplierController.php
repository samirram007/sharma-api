<?php

namespace Modules\Supplier\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Supplier\Contracts\SupplierServiceInterface;
use Modules\Supplier\Resources\SupplierResource;
use Modules\Supplier\Resources\SupplierCollection;
use Modules\Supplier\Requests\SupplierRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected SupplierServiceInterface $service)
    {
    }

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

        $result = $this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result ? 'Supplier deleted successfully' : 'Supplier not found',
        ]);
    }
}
