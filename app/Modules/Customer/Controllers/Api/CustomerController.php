<?php

namespace Modules\Customer\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Customer\Facades\CustomerFacade;
use Modules\Customer\Requests\CustomerRequest;
use Modules\Customer\Resources\CustomerCollection;
use Modules\Customer\Resources\CustomerResource;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = CustomerFacade::getAll();

        return new CustomerCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = CustomerFacade::getById($id);

        return new CustomerResource($data);
    }

    public function store(CustomerRequest $request): SuccessResource
    {
        $data = CustomerFacade::store($request->validated());

        return new CustomerResource($data, $messages = 'Customer created successfully');
    }

    public function update(CustomerRequest $request, int $id): SuccessResource
    {
        $data = CustomerFacade::update($request->validated(), $id);

        return new CustomerResource($data, $messages = 'Customer updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(CustomerFacade::delete($id), 'Customer');
    }
}
