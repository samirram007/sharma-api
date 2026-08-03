<?php

namespace Modules\Payment\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Payment\Facades\PaymentFacade;
use Modules\Payment\Requests\PaymentRequest;
use Modules\Payment\Resources\PaymentCollection;
use Modules\Payment\Resources\PaymentResource;
use Modules\Voucher\Resources\VoucherCollection;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    public function index(): SuccessCollection
    {
        $data = PaymentFacade::getAll();

        return new PaymentCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = PaymentFacade::getById($id);

        return new PaymentResource($data);
    }

    public function store(PaymentRequest $request): SuccessResource
    {
        $data = PaymentFacade::store($request->validated());

        return new PaymentResource($data, $messages = 'Payment created successfully');
    }

    public function update(PaymentRequest $request, int $id): SuccessResource
    {
        $data = PaymentFacade::update($request->validated(), $id);

        return new PaymentResource($data, $messages = 'Payment updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(PaymentFacade::delete($id), 'Payment');
    }

    public function freightPayments(int $freight_id): SuccessCollection
    {
        $data = PaymentFacade::getPaymentsByFreightId($freight_id);

        return new VoucherCollection($data, 'Freight payments fetched successfully');
    }
}
