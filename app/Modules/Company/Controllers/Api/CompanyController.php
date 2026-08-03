<?php

namespace Modules\Company\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Company\Facades\CompanyFacade;
use Modules\Company\Requests\CompanyRequest;
use Modules\Company\Resources\CompanyCollection;
use Modules\Company\Resources\CompanyResource;

class CompanyController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    public function index(): JsonResponse
    {
        $data = CompanyFacade::getAll();

        // dd($data->toArray());
        return (new CompanyCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = CompanyFacade::getById($id);

        return new CompanyResource($data, $messages = 'Company retrieved successfully');

    }

    public function store(CompanyRequest $request): SuccessResource
    {
        $data = CompanyFacade::store($request->validated());

        return new CompanyResource($data, $messages = 'Company created successfully');

    }

    public function update(CompanyRequest $request, int $id): SuccessResource
    {
        $data = CompanyFacade::update($request->validated(), $id);

        return new CompanyResource($data, $messages = 'Company updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(CompanyFacade::delete($id), 'Company');
    }
}
