<?php

namespace Modules\StockJournalGodownEntryPurge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalGodownEntryPurge\Facades\StockJournalGodownEntryPurgeFacade;
use Modules\StockJournalGodownEntryPurge\Requests\StockJournalGodownEntryPurgeRequest;
use Modules\StockJournalGodownEntryPurge\Resources\StockJournalGodownEntryPurgeCollection;
use Modules\StockJournalGodownEntryPurge\Resources\StockJournalGodownEntryPurgeResource;

class StockJournalGodownEntryPurgeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockJournalGodownEntryPurgeFacade::getAll();

        return new StockJournalGodownEntryPurgeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockJournalGodownEntryPurgeFacade::getById($id);

        return new StockJournalGodownEntryPurgeResource($data);
    }

    public function store(StockJournalGodownEntryPurgeRequest $request): SuccessResource
    {
        $data = StockJournalGodownEntryPurgeFacade::store($request->validated());

        return new StockJournalGodownEntryPurgeResource($data, $messages = 'StockJournalGodownEntryPurge created successfully');
    }

    public function update(StockJournalGodownEntryPurgeRequest $request, int $id): SuccessResource
    {
        $data = StockJournalGodownEntryPurgeFacade::update($request->validated(), $id);

        return new StockJournalGodownEntryPurgeResource($data, $messages = 'StockJournalGodownEntryPurge updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockJournalGodownEntryPurgeFacade::delete($id), 'StockJournalGodownEntryPurge');
    }
}
