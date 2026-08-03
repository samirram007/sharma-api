<?php

namespace Modules\ReceiptVoucher\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\ReceiptVoucher\Facades\ReceiptVoucherFacade;
use Modules\ReceiptVoucher\Requests\FreightReceiptVoucherRequest;
use Modules\ReceiptVoucher\Requests\ReceiptVoucherRequest;
use Modules\ReceiptVoucher\Resources\ReceiptVoucherCollection;
use Modules\ReceiptVoucher\Resources\ReceiptVoucherResource;
use Modules\Voucher\Resources\VoucherCollection;
use Modules\Voucher\Resources\VoucherResource;

class ReceiptVoucherController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    public function index(): SuccessCollection
    {
        $data = ReceiptVoucherFacade::getAll();

        return new ReceiptVoucherCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = ReceiptVoucherFacade::getById($id);

        return new VoucherResource($data);
    }

    public function store(ReceiptVoucherRequest $request): SuccessResource
    {
        $data = ReceiptVoucherFacade::store($request->validated());

        return new ReceiptVoucherResource($data, $messages = 'ReceiptVoucher created successfully');
    }

    public function update(ReceiptVoucherRequest $request, int $id): SuccessResource
    {
        $data = ReceiptVoucherFacade::update($request->validated(), $id);

        return new ReceiptVoucherResource($data, $messages = 'ReceiptVoucher updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(ReceiptVoucherFacade::delete($id), 'ReceiptVoucher');
    }

    public function freightReceiptVouchers(int $freight_id): SuccessCollection
    {
        $data = ReceiptVoucherFacade::getFreightReceiptByFreightId($freight_id);

        return new VoucherCollection($data);
    }

    public function storeFreightReceiptVoucher(FreightReceiptVoucherRequest $request): SuccessResource
    {
        $data = ReceiptVoucherFacade::storeFreightReceiptVoucher($request->validated());

        return new VoucherResource($data, $messages = 'Freight ReceiptVoucher created successfully');
    }
}
