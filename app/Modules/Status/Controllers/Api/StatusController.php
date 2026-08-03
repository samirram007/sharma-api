<?php

namespace Modules\Status\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Status\Facades\StatusFacade;
use Modules\Status\Requests\StatusRequest;
use Modules\Status\Resources\StatusCollection;
use Modules\Status\Resources\StatusResource;

class StatusController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StatusFacade::getAll();

        return new StatusCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StatusFacade::getById($id);

        return new StatusResource($data);
    }

    public function store(StatusRequest $request): SuccessResource
    {
        $data = StatusFacade::store($request->validated());

        return new StatusResource($data, $messages = 'Status created successfully');
    }

    public function update(StatusRequest $request, int $id): SuccessResource
    {
        $data = StatusFacade::update($request->validated(), $id);

        return new StatusResource($data, $messages = 'Status updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StatusFacade::delete($id), 'Status');
    }
}
