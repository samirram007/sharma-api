<?php

namespace Modules\VoucherPaymentMode\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherPaymentMode\Facades\VoucherPaymentModeFacade;
use Modules\VoucherPaymentMode\Requests\VoucherPaymentModeRequest;
use Modules\VoucherPaymentMode\Resources\VoucherPaymentModeCollection;
use Modules\VoucherPaymentMode\Resources\VoucherPaymentModeResource;

class VoucherPaymentModeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherPaymentModeFacade::getAll();

        return new VoucherPaymentModeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherPaymentModeFacade::getById($id);

        return new VoucherPaymentModeResource($data);
    }

    public function store(VoucherPaymentModeRequest $request): SuccessResource
    {
        $data = VoucherPaymentModeFacade::store($request->validated());

        return new VoucherPaymentModeResource($data, $messages = 'VoucherPaymentMode created successfully');
    }

    public function update(VoucherPaymentModeRequest $request, int $id): SuccessResource
    {
        $data = VoucherPaymentModeFacade::update($request->validated(), $id);

        return new VoucherPaymentModeResource($data, $messages = 'VoucherPaymentMode updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherPaymentModeFacade::delete($id), 'VoucherPaymentMode');
    }
}
