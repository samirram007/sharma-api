<?php

namespace Modules\HsnSacCode\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\HsnSacCode\Facades\HsnSacCodeFacade;
use Modules\HsnSacCode\Requests\HsnSacCodeRequest;
use Modules\HsnSacCode\Resources\HsnSacCodeCollection;
use Modules\HsnSacCode\Resources\HsnSacCodeResource;

class HsnSacCodeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = HsnSacCodeFacade::getAll();

        return new HsnSacCodeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = HsnSacCodeFacade::getById($id);

        return new HsnSacCodeResource($data);
    }

    public function store(HsnSacCodeRequest $request): SuccessResource
    {
        $data = HsnSacCodeFacade::store($request->validated());

        return new HsnSacCodeResource($data, $messages = 'HsnSacCode created successfully');
    }

    public function update(HsnSacCodeRequest $request, int $id): SuccessResource
    {
        $data = HsnSacCodeFacade::update($request->validated(), $id);

        return new HsnSacCodeResource($data, $messages = 'HsnSacCode updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(HsnSacCodeFacade::delete($id), 'HsnSacCode');
    }
}
