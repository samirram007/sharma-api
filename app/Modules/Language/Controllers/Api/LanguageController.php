<?php

namespace Modules\Language\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Language\Facades\LanguageFacade;
use Modules\Language\Requests\LanguageRequest;
use Modules\Language\Resources\LanguageCollection;
use Modules\Language\Resources\LanguageResource;

class LanguageController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = LanguageFacade::getAll();

        return (new LanguageCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = LanguageFacade::getById($id);

        return $this->resourceResponse(
            new LanguageResource($data),
            'Language retrieved successfully'
        );
    }

    public function store(LanguageRequest $request): JsonResponse
    {
        $data = LanguageFacade::store($request->validated());

        return $this->resourceResponse(
            new LanguageResource($data),
            'Language created successfully',
            201
        );
    }

    public function update(LanguageRequest $request, int $id): JsonResponse
    {
        $data = LanguageFacade::update($request->validated(), $id);

        return $this->resourceResponse(
            new LanguageResource($data),
            'Language updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(LanguageFacade::delete($id), 'Language');
    }
}
