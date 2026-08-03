<?php

namespace Modules\VoucherType\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherType\Facades\VoucherTypeFacade;
use Modules\VoucherType\Requests\VoucherTypeRequest;
use Modules\VoucherType\Resources\VoucherTypeCollection;
use Modules\VoucherType\Resources\VoucherTypeResource;

class VoucherTypeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherTypeFacade::getAll();

        return new VoucherTypeCollection($data);
    }

    public function show(int $id): ?SuccessResource
    {
        $data = VoucherTypeFacade::getById($id);

        return new VoucherTypeResource($data, $message = 'VoucherType retrieved successfully');

    }

    public function store(VoucherTypeRequest $request): SuccessResource
    {
        $data = VoucherTypeFacade::store($request->validated());

        return new VoucherTypeResource($data, $message = 'VoucherType created successfully');

    }

    public function update(VoucherTypeRequest $request, int $id): SuccessResource
    {
        $data = VoucherTypeFacade::update($request->validated(), $id);

        return new VoucherTypeResource($data, $message = 'VoucherType updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherTypeFacade::delete($id), 'VoucherType');
    }
}
