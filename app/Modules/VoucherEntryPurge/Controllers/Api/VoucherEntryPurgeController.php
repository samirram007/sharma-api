<?php

namespace Modules\VoucherEntryPurge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeServiceInterface;
use Modules\VoucherEntryPurge\Requests\VoucherEntryPurgeRequest;
use Modules\VoucherEntryPurge\Resources\VoucherEntryPurgeCollection;
use Modules\VoucherEntryPurge\Resources\VoucherEntryPurgeResource;

class VoucherEntryPurgeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected VoucherEntryPurgeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new VoucherEntryPurgeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new VoucherEntryPurgeResource($data);
    }

    public function store(VoucherEntryPurgeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new VoucherEntryPurgeResource($data, $messages = 'VoucherEntryPurge created successfully');
    }

    public function update(VoucherEntryPurgeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new VoucherEntryPurgeResource($data, $messages = 'VoucherEntryPurge updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'VoucherEntryPurge');
    }
}
