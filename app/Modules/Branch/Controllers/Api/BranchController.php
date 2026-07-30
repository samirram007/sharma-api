<?php

namespace Modules\Branch\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Branch\Contracts\BranchServiceInterface;
use Modules\Branch\Requests\BranchRequest;
use Modules\Branch\Resources\BranchCollection;
use Modules\Branch\Resources\BranchResource;

class BranchController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected BranchServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new BranchCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new BranchResource($data);
    }

    public function store(BranchRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new BranchResource($data, $messages = 'Branch created successfully');
    }

    public function update(BranchRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new BranchResource($data, $messages = 'Branch updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Branch');
    }
}
