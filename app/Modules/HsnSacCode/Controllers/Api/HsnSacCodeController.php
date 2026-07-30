<?php

namespace Modules\HsnSacCode\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\HsnSacCode\Contracts\HsnSacCodeServiceInterface;
use Modules\HsnSacCode\Requests\HsnSacCodeRequest;
use Modules\HsnSacCode\Resources\HsnSacCodeCollection;
use Modules\HsnSacCode\Resources\HsnSacCodeResource;

class HsnSacCodeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected HsnSacCodeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new HsnSacCodeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new HsnSacCodeResource($data);
    }

    public function store(HsnSacCodeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new HsnSacCodeResource($data, $messages = 'HsnSacCode created successfully');
    }

    public function update(HsnSacCodeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new HsnSacCodeResource($data, $messages = 'HsnSacCode updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'HsnSacCode');
    }
}
