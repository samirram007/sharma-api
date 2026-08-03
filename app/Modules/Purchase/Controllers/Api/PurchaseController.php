<?php

namespace Modules\Purchase\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Purchase\Facades\PurchaseFacade;
use Modules\Purchase\Requests\PurchaseRequest;
use Modules\Purchase\Resources\PurchaseCollection;
use Modules\Purchase\Resources\PurchaseResource;

class PurchaseController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = PurchaseFacade::getAll();

        return new PurchaseCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = PurchaseFacade::getById($id);

        return new PurchaseResource($data);
    }

    public function store(PurchaseRequest $request): SuccessResource
    {
        $data = PurchaseFacade::store($request->validated());

        return new PurchaseResource($data, $messages = 'Purchase created successfully');
    }

    public function update(PurchaseRequest $request, int $id): SuccessResource
    {
        $data = PurchaseFacade::update($request->validated(), $id);

        return new PurchaseResource($data, $messages = 'Purchase updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(PurchaseFacade::delete($id), 'Purchase');
    }
}
