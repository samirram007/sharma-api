<?php

namespace Modules\TestItem\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\TestItem\Facades\TestItemFacade;
use Modules\TestItem\Requests\TestItemRequest;
use Modules\TestItem\Resources\TestItemCollection;
use Modules\TestItem\Resources\TestItemResource;

class TestItemController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = TestItemFacade::getAll();

        return new TestItemCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = TestItemFacade::getById($id);

        return new TestItemResource($data);
    }

    public function store(TestItemRequest $request): SuccessResource
    {
        $data = TestItemFacade::store($request->validated());

        return new TestItemResource($data, $messages = 'TestItem created successfully');
    }

    public function update(TestItemRequest $request, int $id): SuccessResource
    {
        $data = TestItemFacade::update($request->validated(), $id);

        return new TestItemResource($data, $messages = 'TestItem updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(TestItemFacade::delete($id), 'TestItem');
    }
}
