<?php

namespace Modules\PaymentVoucher\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\PaymentVoucher\Facades\PaymentVoucherFacade;
use Modules\PaymentVoucher\Requests\PaymentVoucherRequest;
use Modules\PaymentVoucher\Resources\PaymentVoucherCollection;
use Modules\PaymentVoucher\Resources\PaymentVoucherResource;

class PaymentVoucherController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = PaymentVoucherFacade::getAll();

        return new PaymentVoucherCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = PaymentVoucherFacade::getById($id);

        return new PaymentVoucherResource($data);
    }

    public function store(PaymentVoucherRequest $request): SuccessResource
    {
        $data = PaymentVoucherFacade::store($request->validated());

        return new PaymentVoucherResource($data, $messages = 'PaymentVoucher created successfully');
    }

    public function update(PaymentVoucherRequest $request, int $id): SuccessResource
    {
        $data = PaymentVoucherFacade::update($request->validated(), $id);

        return new PaymentVoucherResource($data, $messages = 'PaymentVoucher updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(PaymentVoucherFacade::delete($id), 'PaymentVoucher');
    }
}
