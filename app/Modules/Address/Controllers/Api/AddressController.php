<?php

namespace Modules\Address\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Address\Facades\AddressFacade;
use Modules\Address\Requests\AddressRequest;
use Modules\Address\Resources\AddressCollection;
use Modules\Address\Resources\AddressResource;

class AddressController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = AddressFacade::getAll();

        return new AddressCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = AddressFacade::getById($id);

        return new AddressResource($data);
    }

    public function store(AddressRequest $request): SuccessResource
    {
        $data = AddressFacade::store($request->validated());

        return new AddressResource($data, $messages = 'Address created successfully');
    }

    public function update(AddressRequest $request, int $id): SuccessResource
    {
        $data = AddressFacade::update($request->validated(), $id);

        return new AddressResource($data, $messages = 'Address updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(AddressFacade::delete($id), 'Address');
    }
}
