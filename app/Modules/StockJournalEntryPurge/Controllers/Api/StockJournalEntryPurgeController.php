<?php

namespace Modules\StockJournalEntryPurge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalEntryPurge\Facades\StockJournalEntryPurgeFacade;
use Modules\StockJournalEntryPurge\Requests\StockJournalEntryPurgeRequest;
use Modules\StockJournalEntryPurge\Resources\StockJournalEntryPurgeCollection;
use Modules\StockJournalEntryPurge\Resources\StockJournalEntryPurgeResource;

class StockJournalEntryPurgeController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockJournalEntryPurgeFacade::getAll();

        return new StockJournalEntryPurgeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockJournalEntryPurgeFacade::getById($id);

        return new StockJournalEntryPurgeResource($data);
    }

    public function store(StockJournalEntryPurgeRequest $request): SuccessResource
    {
        $data = StockJournalEntryPurgeFacade::store($request->validated());

        return new StockJournalEntryPurgeResource($data, $messages = 'StockJournalEntryPurge created successfully');
    }

    public function update(StockJournalEntryPurgeRequest $request, int $id): SuccessResource
    {
        $data = StockJournalEntryPurgeFacade::update($request->validated(), $id);

        return new StockJournalEntryPurgeResource($data, $messages = 'StockJournalEntryPurge updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockJournalEntryPurgeFacade::delete($id), 'StockJournalEntryPurge');
    }
}
