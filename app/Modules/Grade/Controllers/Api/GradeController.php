<?php

namespace Modules\Grade\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Grade\Contracts\GradeServiceInterface;
use Modules\Grade\Requests\GradeRequest;
use Modules\Grade\Resources\GradeCollection;
use Modules\Grade\Resources\GradeResource;

class GradeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected GradeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new GradeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new GradeResource($data);
    }

    public function store(GradeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new GradeResource($data, $messages = 'Grade created successfully');
    }

    public function update(GradeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new GradeResource($data, $messages = 'Grade updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Grade');
    }
}
