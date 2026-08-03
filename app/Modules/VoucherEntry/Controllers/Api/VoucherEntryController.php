<?php

namespace Modules\VoucherEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherEntry\Facades\VoucherEntryFacade;
use Modules\VoucherEntry\Requests\VoucherEntryRequest;
use Modules\VoucherEntry\Resources\VoucherEntryCollection;
use Modules\VoucherEntry\Resources\VoucherEntryResource;

class VoucherEntryController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherEntryFacade::getAll();

        return new VoucherEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherEntryFacade::getById($id);

        return new VoucherEntryResource($data);
    }

    public function store(VoucherEntryRequest $request): SuccessResource
    {
        $data = VoucherEntryFacade::store($request->validated());

        return new VoucherEntryResource($data, $messages = 'VoucherEntry created successfully');
    }

    public function update(VoucherEntryRequest $request, int $id): SuccessResource
    {
        $data = VoucherEntryFacade::update($request->validated(), $id);

        return new VoucherEntryResource($data, $messages = 'VoucherEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherEntryFacade::delete($id), 'VoucherEntry');
    }
}
