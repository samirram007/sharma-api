<?php

namespace Modules\Faq\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Faq\Facades\FaqFacade;
use Modules\Faq\Requests\FaqRequest;
use Modules\Faq\Resources\FaqCollection;
use Modules\Faq\Resources\FaqResource;

class FaqController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = FaqFacade::getAll();

        return new FaqCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = FaqFacade::getById($id);

        return new FaqResource($data);
    }

    public function store(FaqRequest $request): SuccessResource
    {
        $data = FaqFacade::store($request->validated());

        return new FaqResource($data, 'FAQ created successfully');
    }

    public function update(FaqRequest $request, int $id): SuccessResource
    {
        $data = FaqFacade::update($request->validated(), $id);

        return new FaqResource($data, 'FAQ updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(FaqFacade::delete($id), 'FAQ');
    }
}
