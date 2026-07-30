<?php

namespace Modules\PaymentVoucher\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\PaymentVoucher\Contracts\PaymentVoucherServiceInterface;
use Modules\PaymentVoucher\Requests\PaymentVoucherRequest;
use Modules\PaymentVoucher\Resources\PaymentVoucherCollection;
use Modules\PaymentVoucher\Resources\PaymentVoucherResource;

class PaymentVoucherController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PaymentVoucherServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new PaymentVoucherCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new PaymentVoucherResource($data);
    }

    public function store(PaymentVoucherRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new PaymentVoucherResource($data, $messages = 'PaymentVoucher created successfully');
    }

    public function update(PaymentVoucherRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new PaymentVoucherResource($data, $messages = 'PaymentVoucher updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'PaymentVoucher');
    }
}
