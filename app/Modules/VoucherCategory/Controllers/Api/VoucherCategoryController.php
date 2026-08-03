<?php

namespace Modules\VoucherCategory\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherCategory\Facades\VoucherCategoryFacade;
use Modules\VoucherCategory\Requests\VoucherCategoryRequest;
use Modules\VoucherCategory\Resources\VoucherCategoryCollection;
use Modules\VoucherCategory\Resources\VoucherCategoryResource;

class VoucherCategoryController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherCategoryFacade::getAll();

        return new VoucherCategoryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherCategoryFacade::getById($id);

        return new VoucherCategoryResource($data);
    }

    public function store(VoucherCategoryRequest $request): SuccessResource
    {
        $data = VoucherCategoryFacade::store($request->validated());

        return new VoucherCategoryResource($data, $messages = 'VoucherCategory created successfully');
    }

    public function update(VoucherCategoryRequest $request, int $id): SuccessResource
    {
        $data = VoucherCategoryFacade::update($request->validated(), $id);

        return new VoucherCategoryResource($data, $messages = 'VoucherCategory updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherCategoryFacade::delete($id), 'VoucherCategory');
    }
}
