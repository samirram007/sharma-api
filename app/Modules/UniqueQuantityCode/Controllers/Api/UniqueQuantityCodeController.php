<?php

namespace Modules\UniqueQuantityCode\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeServiceInterface;
use Modules\UniqueQuantityCode\Requests\UniqueQuantityCodeRequest;
use Modules\UniqueQuantityCode\Resources\UniqueQuantityCodeCollection;
use Modules\UniqueQuantityCode\Resources\UniqueQuantityCodeResource;

class UniqueQuantityCodeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected UniqueQuantityCodeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new UniqueQuantityCodeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new UniqueQuantityCodeResource($data);
    }

    public function store(UniqueQuantityCodeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new UniqueQuantityCodeResource($data, $messages = 'UniqueQuantityCode created successfully');
    }

    public function update(UniqueQuantityCodeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new UniqueQuantityCodeResource($data, $messages = 'UniqueQuantityCode updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'UniqueQuantityCode');
    }
}
