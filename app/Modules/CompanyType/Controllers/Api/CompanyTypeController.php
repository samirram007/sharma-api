<?php

namespace Modules\CompanyType\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\CompanyType\Contracts\CompanyTypeServiceInterface;
use Modules\CompanyType\Requests\CompanyTypeRequest;
use Modules\CompanyType\Resources\CompanyTypeCollection;
use Modules\CompanyType\Resources\CompanyTypeResource;

class CompanyTypeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected CompanyTypeServiceInterface $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();

        return (new CompanyTypeCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getById($id);

        return $this->resourceResponse(
            new CompanyTypeResource($data),
            'CompanyType retrieved successfully'
        );
    }

    public function store(CompanyTypeRequest $request): JsonResponse
    {
        $data = $this->service->store($request->validated());

        return $this->resourceResponse(
            new CompanyTypeResource($data),
            'CompanyType created successfully',
            201
        );
    }

    public function update(CompanyTypeRequest $request, int $id): JsonResponse
    {
        $data = $this->service->update($request->validated(), $id);

        return $this->resourceResponse(
            new CompanyTypeResource($data),
            'CompanyType updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'CompanyType');
    }
}
