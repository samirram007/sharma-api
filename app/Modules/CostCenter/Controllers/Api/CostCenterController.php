<?php

namespace Modules\CostCenter\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\CostCenter\Contracts\CostCenterServiceInterface;
use Modules\CostCenter\Resources\CostCenterResource;
use Modules\CostCenter\Resources\CostCenterCollection;
use Modules\CostCenter\Requests\CostCenterRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CostCenterController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected CostCenterServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();
        return new CostCenterCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return  new CostCenterResource($data);
    }

    public function store(CostCenterRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
       return  new CostCenterResource($data, $messages='CostCenter created successfully');
    }

    public function update(CostCenterRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return  new CostCenterResource($data, $messages='CostCenter updated successfully');
    }

        public function destroy(int $id): JsonResponse
    {

        $result=$this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result?'CostCenter deleted successfully':'CostCenter not found',
        ]);
    }
}
