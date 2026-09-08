<?php

namespace Modules\Journal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Journal\Facades\JournalFacade;
use Modules\Journal\Requests\JournalRequest;
use Modules\Journal\Resources\JournalCollection;
use Modules\Journal\Resources\JournalResource;

class JournalController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = JournalFacade::getAll();

        return (new JournalCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = JournalFacade::getById($id);

        return new JournalResource($data, 'Journal retrieved successfully');
    }

    public function store(JournalRequest $request): SuccessResource
    {
        $data = JournalFacade::store($request->validated());

        return new JournalResource($data, 'Journal created successfully');
    }

    public function update(JournalRequest $request, int $id): SuccessResource
    {
        $data = JournalFacade::update($request->validated(), $id);

        return new JournalResource($data, 'Journal updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(JournalFacade::delete($id), 'Journal');
    }
}
