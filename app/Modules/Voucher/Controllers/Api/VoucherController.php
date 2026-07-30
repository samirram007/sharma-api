<?php

namespace Modules\Voucher\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Voucher\Contracts\VoucherServiceInterface;
use Modules\Voucher\Requests\VoucherRequest;
use Modules\Voucher\Resources\VoucherCollection;
use Modules\Voucher\Resources\VoucherPrintResource;
use Modules\Voucher\Resources\VoucherResource;

class VoucherController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected VoucherServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        // dd($data);
        return new VoucherCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new VoucherResource($data);
    }

    public function store(VoucherRequest $request): SuccessResource
    {

        $data = $this->service->store($request->validated());

        return new VoucherResource($data, $messages = 'Voucher created successfully');
    }

    public function update(VoucherRequest $request, int $id): SuccessResource
    {
        // dd($id, $request->validated());
        $data = $this->service->update($request->validated(), $id);

        return new VoucherResource($data, $messages = 'Voucher updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Voucher');
    }

    public function print(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new VoucherPrintResource($data);
    }
}
