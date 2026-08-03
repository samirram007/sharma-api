<?php

namespace Modules\GstRegistrationType\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\GstRegistrationType\Facades\GstRegistrationTypeFacade;
use Modules\GstRegistrationType\Requests\GstRegistrationTypeRequest;
use Modules\GstRegistrationType\Resources\GstRegistrationTypeCollection;
use Modules\GstRegistrationType\Resources\GstRegistrationTypeResource;

class GstRegistrationTypeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = GstRegistrationTypeFacade::getAll();

        return new GstRegistrationTypeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = GstRegistrationTypeFacade::getById($id);

        return new GstRegistrationTypeResource($data);
    }

    public function store(GstRegistrationTypeRequest $request): SuccessResource
    {
        $data = GstRegistrationTypeFacade::store($request->validated());

        return new GstRegistrationTypeResource($data, $messages = 'GstRegistrationType created successfully');
    }

    public function update(GstRegistrationTypeRequest $request, int $id): SuccessResource
    {
        $data = GstRegistrationTypeFacade::update($request->validated(), $id);

        return new GstRegistrationTypeResource($data, $messages = 'GstRegistrationType updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(GstRegistrationTypeFacade::delete($id), 'GstRegistrationType');
    }
}
