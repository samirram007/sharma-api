<?php

namespace Modules\Address\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Address\Contracts\AddressServiceInterface;
use Modules\Address\Requests\AddressRequest;
use Modules\Address\Resources\AddressCollection;
use Modules\Address\Resources\AddressResource;

class AddressController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AddressServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new AddressCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new AddressResource($data);
    }

    public function store(AddressRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new AddressResource($data, $messages = 'Address created successfully');
    }

    public function update(AddressRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new AddressResource($data, $messages = 'Address updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Address');
    }
}
