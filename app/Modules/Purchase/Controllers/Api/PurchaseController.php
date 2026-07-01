<?php

namespace Modules\Purchase\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Purchase\Contracts\PurchaseServiceInterface;
use Modules\Purchase\Resources\PurchaseResource;
use Modules\Purchase\Resources\PurchaseCollection;
use Modules\Purchase\Requests\PurchaseRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PurchaseServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();
        return new PurchaseCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return  new PurchaseResource($data);
    }

    public function store(PurchaseRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
       return  new PurchaseResource($data, $messages='Purchase created successfully');
    }

    public function update(PurchaseRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return  new PurchaseResource($data, $messages='Purchase updated successfully');
    }

        public function destroy(int $id): JsonResponse
    {

        $result=$this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result?'Purchase deleted successfully':'Purchase not found',
        ]);
    }
}
