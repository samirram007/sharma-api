<?php

namespace Modules\GstRegistrationType\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeServiceInterface;
use Modules\GstRegistrationType\Requests\GstRegistrationTypeRequest;
use Modules\GstRegistrationType\Resources\GstRegistrationTypeCollection;
use Modules\GstRegistrationType\Resources\GstRegistrationTypeResource;

class GstRegistrationTypeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected GstRegistrationTypeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new GstRegistrationTypeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new GstRegistrationTypeResource($data);
    }

    public function store(GstRegistrationTypeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new GstRegistrationTypeResource($data, $messages = 'GstRegistrationType created successfully');
    }

    public function update(GstRegistrationTypeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new GstRegistrationTypeResource($data, $messages = 'GstRegistrationType updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'GstRegistrationType');
    }
}
