<?php

namespace Modules\Shift\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Shift\Contracts\ShiftServiceInterface;
use Modules\Shift\Requests\ShiftRequest;
use Modules\Shift\Resources\ShiftCollection;
use Modules\Shift\Resources\ShiftResource;

class ShiftController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ShiftServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new ShiftCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new ShiftResource($data);
    }

    public function store(ShiftRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new ShiftResource($data, $messages = 'Shift created successfully');
    }

    public function update(ShiftRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new ShiftResource($data, $messages = 'Shift updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Shift');
    }
}
