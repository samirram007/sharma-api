<?php

namespace Modules\Company\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Company\Contracts\CompanyServiceInterface;
use Modules\Company\Resources\CompanyResource;
use Modules\Company\Resources\CompanyCollection;
use Modules\Company\Requests\CompanyRequest;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected CompanyServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');
        $data = $this->service->getAll($status);
      //dd($data->toArray());
        return (new CompanyCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return new CompanyResource($data, $messages = 'Company retrieved successfully');


    }

    public function store(CompanyRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
        return new CompanyResource($data, $messages = 'Company created successfully');

    }

    public function update(CompanyRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return new CompanyResource($data, $messages = 'Company updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {

        $result = $this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result ? 'Company deleted successfully' : 'Company not found',
        ]);

    }
}
