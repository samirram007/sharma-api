<?php

namespace Modules\VoucherEntryPurge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherEntryPurge\Facades\VoucherEntryPurgeFacade;
use Modules\VoucherEntryPurge\Requests\VoucherEntryPurgeRequest;
use Modules\VoucherEntryPurge\Resources\VoucherEntryPurgeCollection;
use Modules\VoucherEntryPurge\Resources\VoucherEntryPurgeResource;

class VoucherEntryPurgeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherEntryPurgeFacade::getAll();

        return new VoucherEntryPurgeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherEntryPurgeFacade::getById($id);

        return new VoucherEntryPurgeResource($data);
    }

    public function store(VoucherEntryPurgeRequest $request): SuccessResource
    {
        $data = VoucherEntryPurgeFacade::store($request->validated());

        return new VoucherEntryPurgeResource($data, $messages = 'VoucherEntryPurge created successfully');
    }

    public function update(VoucherEntryPurgeRequest $request, int $id): SuccessResource
    {
        $data = VoucherEntryPurgeFacade::update($request->validated(), $id);

        return new VoucherEntryPurgeResource($data, $messages = 'VoucherEntryPurge updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherEntryPurgeFacade::delete($id), 'VoucherEntryPurge');
    }
}
