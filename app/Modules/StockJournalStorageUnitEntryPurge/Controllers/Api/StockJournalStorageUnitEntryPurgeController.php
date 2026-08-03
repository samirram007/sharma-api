<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalStorageUnitEntryPurge\Facades\StockJournalStorageUnitEntryPurgeFacade;
use Modules\StockJournalStorageUnitEntryPurge\Requests\StockJournalStorageUnitEntryPurgeRequest;
use Modules\StockJournalStorageUnitEntryPurge\Resources\StockJournalStorageUnitEntryPurgeCollection;
use Modules\StockJournalStorageUnitEntryPurge\Resources\StockJournalStorageUnitEntryPurgeResource;

class StockJournalStorageUnitEntryPurgeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockJournalStorageUnitEntryPurgeFacade::getAll();

        return new StockJournalStorageUnitEntryPurgeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockJournalStorageUnitEntryPurgeFacade::getById($id);

        return new StockJournalStorageUnitEntryPurgeResource($data);
    }

    public function store(StockJournalStorageUnitEntryPurgeRequest $request): SuccessResource
    {
        $data = StockJournalStorageUnitEntryPurgeFacade::store($request->validated());

        return new StockJournalStorageUnitEntryPurgeResource($data, $messages = 'StockJournalStorageUnitEntryPurge created successfully');
    }

    public function update(StockJournalStorageUnitEntryPurgeRequest $request, int $id): SuccessResource
    {
        $data = StockJournalStorageUnitEntryPurgeFacade::update($request->validated(), $id);

        return new StockJournalStorageUnitEntryPurgeResource($data, $messages = 'StockJournalStorageUnitEntryPurge updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockJournalStorageUnitEntryPurgeFacade::delete($id), 'StockJournalStorageUnitEntryPurge');
    }
}
