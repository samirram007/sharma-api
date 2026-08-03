<?php

namespace Modules\Uqc\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Uqc\Facades\UqcFacade;
use Modules\Uqc\Requests\UqcRequest;
use Modules\Uqc\Resources\UqcCollection;
use Modules\Uqc\Resources\UqcResource;

class UqcController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = UqcFacade::getAll();

        return new UqcCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = UqcFacade::getById($id);

        return new UqcResource($data);
    }

    public function store(UqcRequest $request): SuccessResource
    {
        $data = UqcFacade::store($request->validated());

        return new UqcResource($data, $messages = 'Uqc created successfully');
    }

    public function update(UqcRequest $request, int $id): SuccessResource
    {
        $data = UqcFacade::update($request->validated(), $id);

        return new UqcResource($data, $messages = 'Uqc updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(UqcFacade::delete($id), 'Uqc');
    }
}
