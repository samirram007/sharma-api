<?php

namespace Modules\Shift\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Shift\Facades\ShiftFacade;
use Modules\Shift\Requests\ShiftRequest;
use Modules\Shift\Resources\ShiftCollection;
use Modules\Shift\Resources\ShiftResource;

class ShiftController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = ShiftFacade::getAll();

        return new ShiftCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = ShiftFacade::getById($id);

        return new ShiftResource($data);
    }

    public function store(ShiftRequest $request): SuccessResource
    {
        $data = ShiftFacade::store($request->validated());

        return new ShiftResource($data, $messages = 'Shift created successfully');
    }

    public function update(ShiftRequest $request, int $id): SuccessResource
    {
        $data = ShiftFacade::update($request->validated(), $id);

        return new ShiftResource($data, $messages = 'Shift updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(ShiftFacade::delete($id), 'Shift');
    }
}
