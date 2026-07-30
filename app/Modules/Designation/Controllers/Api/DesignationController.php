<?php

namespace Modules\Designation\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Designation\Contracts\DesignationServiceInterface;
use Modules\Designation\Requests\DesignationRequest;
use Modules\Designation\Resources\DesignationCollection;
use Modules\Designation\Resources\DesignationResource;

class DesignationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DesignationServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new DesignationCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DesignationResource($data);
    }

    public function store(DesignationRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DesignationResource($data, $messages = 'Designation created successfully');
    }

    public function update(DesignationRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new DesignationResource($data, $messages = 'Designation updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Designation');
    }
}
