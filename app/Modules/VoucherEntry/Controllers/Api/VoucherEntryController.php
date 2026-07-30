<?php

namespace Modules\VoucherEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherEntry\Requests\VoucherEntryRequest;
use Modules\VoucherEntry\Resources\VoucherEntryCollection;
use Modules\VoucherEntry\Resources\VoucherEntryResource;

class VoucherEntryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected VoucherEntryServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new VoucherEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new VoucherEntryResource($data);
    }

    public function store(VoucherEntryRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new VoucherEntryResource($data, $messages = 'VoucherEntry created successfully');
    }

    public function update(VoucherEntryRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new VoucherEntryResource($data, $messages = 'VoucherEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'VoucherEntry');
    }
}
