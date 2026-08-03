<?php

namespace Modules\StockJournalEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalEntry\Facades\StockJournalEntryFacade;
use Modules\StockJournalEntry\Requests\StockJournalEntryRequest;
use Modules\StockJournalEntry\Resources\StockJournalEntryCollection;
use Modules\StockJournalEntry\Resources\StockJournalEntryResource;

class StockJournalEntryController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockJournalEntryFacade::getAll();

        return new StockJournalEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockJournalEntryFacade::getById($id);

        return new StockJournalEntryResource($data);
    }

    public function store(StockJournalEntryRequest $request): SuccessResource
    {
        $data = StockJournalEntryFacade::store($request->validated());

        return new StockJournalEntryResource($data, $messages = 'StockJournalEntry created successfully');
    }

    public function update(StockJournalEntryRequest $request, int $id): SuccessResource
    {
        $data = StockJournalEntryFacade::update($request->validated(), $id);

        return new StockJournalEntryResource($data, $messages = 'StockJournalEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockJournalEntryFacade::delete($id), 'StockJournalEntry');
    }
}
