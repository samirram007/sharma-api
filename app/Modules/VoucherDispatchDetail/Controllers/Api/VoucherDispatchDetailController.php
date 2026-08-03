<?php

namespace Modules\VoucherDispatchDetail\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherDispatchDetail\Facades\VoucherDispatchDetailFacade;
use Modules\VoucherDispatchDetail\Requests\VoucherDispatchDetailRequest;
use Modules\VoucherDispatchDetail\Resources\VoucherDispatchDetailCollection;
use Modules\VoucherDispatchDetail\Resources\VoucherDispatchDetailResource;

class VoucherDispatchDetailController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherDispatchDetailFacade::getAll();

        return new VoucherDispatchDetailCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherDispatchDetailFacade::getById($id);

        return new VoucherDispatchDetailResource($data);
    }

    public function store(VoucherDispatchDetailRequest $request): SuccessResource
    {
        $data = VoucherDispatchDetailFacade::store($request->validated());

        return new VoucherDispatchDetailResource($data, $messages = 'VoucherDispatchDetail created successfully');
    }

    public function update(VoucherDispatchDetailRequest $request, int $id): SuccessResource
    {
        $data = VoucherDispatchDetailFacade::update($request->validated(), $id);

        return new VoucherDispatchDetailResource($data, $messages = 'VoucherDispatchDetail updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherDispatchDetailFacade::delete($id), 'VoucherDispatchDetail');
    }
}
