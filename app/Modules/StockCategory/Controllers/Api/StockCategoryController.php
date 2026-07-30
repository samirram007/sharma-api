<?php

namespace Modules\StockCategory\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockCategory\Contracts\StockCategoryServiceInterface;
use Modules\StockCategory\Requests\StockCategoryRequest;
use Modules\StockCategory\Resources\StockCategoryCollection;
use Modules\StockCategory\Resources\StockCategoryResource;

class StockCategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockCategoryServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockCategoryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockCategoryResource($data);
    }

    public function store(StockCategoryRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockCategoryResource($data, $messages = 'StockCategory created successfully');
    }

    public function update(StockCategoryRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockCategoryResource($data, $messages = 'StockCategory updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockCategory');
    }
}
