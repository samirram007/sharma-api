<?php

namespace Modules\CompanyType\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\CompanyType\Facades\CompanyTypeFacade;
use Modules\CompanyType\Requests\CompanyTypeRequest;
use Modules\CompanyType\Resources\CompanyTypeCollection;
use Modules\CompanyType\Resources\CompanyTypeResource;

class CompanyTypeController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = CompanyTypeFacade::getAll();

        return (new CompanyTypeCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = CompanyTypeFacade::getById($id);

        return $this->resourceResponse(
            new CompanyTypeResource($data),
            'CompanyType retrieved successfully'
        );
    }

    public function store(CompanyTypeRequest $request): JsonResponse
    {
        $data = CompanyTypeFacade::store($request->validated());

        return $this->resourceResponse(
            new CompanyTypeResource($data),
            'CompanyType created successfully',
            201
        );
    }

    public function update(CompanyTypeRequest $request, int $id): JsonResponse
    {
        $data = CompanyTypeFacade::update($request->validated(), $id);

        return $this->resourceResponse(
            new CompanyTypeResource($data),
            'CompanyType updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(CompanyTypeFacade::delete($id), 'CompanyType');
    }
}
