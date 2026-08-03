<?php

namespace Modules\State\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\State\Facades\StateFacade;
use Modules\State\Requests\StateRequest;
use Modules\State\Resources\StateCollection;
use Modules\State\Resources\StateResource;

class StateController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = StateFacade::getAll();

        return (new StateCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = StateFacade::getById($id);

        return new StateResource($data, $messages = 'State retrieved successfully');

    }

    public function store(StateRequest $request): SuccessResource
    {
        $data = StateFacade::store($request->validated());

        return new StateResource($data, $messages = 'State created successfully');

    }

    public function update(StateRequest $request, int $id): SuccessResource
    {
        $data = StateFacade::update($request->validated(), $id);

        return new StateResource($data, $messages = 'State updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StateFacade::delete($id), 'State');
    }
}
