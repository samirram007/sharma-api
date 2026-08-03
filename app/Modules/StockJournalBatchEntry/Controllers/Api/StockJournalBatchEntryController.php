<?php

namespace Modules\StockJournalBatchEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalBatchEntry\Facades\StockJournalBatchEntryFacade;
use Modules\StockJournalBatchEntry\Requests\StockJournalBatchEntryRequest;
use Modules\StockJournalBatchEntry\Resources\StockJournalBatchEntryCollection;
use Modules\StockJournalBatchEntry\Resources\StockJournalBatchEntryResource;

class StockJournalBatchEntryController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockJournalBatchEntryFacade::getAll();

        return new StockJournalBatchEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockJournalBatchEntryFacade::getById($id);

        return new StockJournalBatchEntryResource($data);
    }

    public function store(StockJournalBatchEntryRequest $request): SuccessResource
    {
        $data = StockJournalBatchEntryFacade::store($request->validated());

        return new StockJournalBatchEntryResource($data, $messages = 'StockJournalBatchEntry created successfully');
    }

    public function update(StockJournalBatchEntryRequest $request, int $id): SuccessResource
    {
        $data = StockJournalBatchEntryFacade::update($request->validated(), $id);

        return new StockJournalBatchEntryResource($data, $messages = 'StockJournalBatchEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockJournalBatchEntryFacade::delete($id), 'StockJournalBatchEntry');
    }
}
