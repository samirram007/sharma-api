<?php

namespace Modules\LeaveType\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\LeaveType\Contracts\LeaveTypeServiceInterface;
use Modules\LeaveType\Resources\LeaveTypeResource;
use Modules\LeaveType\Resources\LeaveTypeCollection;
use Modules\LeaveType\Requests\LeaveTypeRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected LeaveTypeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();
        return new LeaveTypeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return  new LeaveTypeResource($data);
    }

    public function store(LeaveTypeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
       return  new LeaveTypeResource($data, $messages='LeaveType created successfully');
    }

    public function update(LeaveTypeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return  new LeaveTypeResource($data, $messages='LeaveType updated successfully');
    }

        public function destroy(int $id): JsonResponse
    {

        $result=$this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result?'LeaveType deleted successfully':'LeaveType not found',
        ]);
    }
}
