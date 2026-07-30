<?php

namespace Modules\CostAllocationRule\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\CostAllocationRule\Contracts\CostAllocationRuleServiceInterface;
use Modules\CostAllocationRule\Requests\CostAllocationRuleRequest;
use Modules\CostAllocationRule\Resources\CostAllocationRuleCollection;
use Modules\CostAllocationRule\Resources\CostAllocationRuleResource;

class CostAllocationRuleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected CostAllocationRuleServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new CostAllocationRuleCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new CostAllocationRuleResource($data);
    }

    public function store(CostAllocationRuleRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new CostAllocationRuleResource($data, $messages = 'CostAllocationRule created successfully');
    }

    public function update(CostAllocationRuleRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new CostAllocationRuleResource($data, $messages = 'CostAllocationRule updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'CostAllocationRule');
    }
}
