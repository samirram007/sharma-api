<?php

namespace Modules\Grade\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Grade\Facades\GradeFacade;
use Modules\Grade\Requests\GradeRequest;
use Modules\Grade\Resources\GradeCollection;
use Modules\Grade\Resources\GradeResource;

class GradeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = GradeFacade::getAll();

        return new GradeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = GradeFacade::getById($id);

        return new GradeResource($data);
    }

    public function store(GradeRequest $request): SuccessResource
    {
        $data = GradeFacade::store($request->validated());

        return new GradeResource($data, $messages = 'Grade created successfully');
    }

    public function update(GradeRequest $request, int $id): SuccessResource
    {
        $data = GradeFacade::update($request->validated(), $id);

        return new GradeResource($data, $messages = 'Grade updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(GradeFacade::delete($id), 'Grade');
    }
}
