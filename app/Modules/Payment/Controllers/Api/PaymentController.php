<?php

namespace Modules\Payment\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Requests\PaymentRequest;
use Modules\Payment\Resources\PaymentCollection;
use Modules\Payment\Resources\PaymentResource;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PaymentServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new PaymentCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new PaymentResource($data);
    }

    public function store(PaymentRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new PaymentResource($data, $messages = 'Payment created successfully');
    }

    public function update(PaymentRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new PaymentResource($data, $messages = 'Payment updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Payment');
    }

    public function freightPayments(int $freight_id): SuccessCollection
    {
        $data = $this->service->getPaymentsByFreightId($freight_id);

        return new PaymentCollection($data);
    }
}
