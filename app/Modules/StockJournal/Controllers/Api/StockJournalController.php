<?php

namespace Modules\StockJournal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournal\Facades\StockJournalFacade;
use Modules\StockJournal\Requests\StockJournalRequest;
use Modules\StockJournal\Resources\StockJournalCollection;
use Modules\StockJournal\Resources\StockJournalResource;

class StockJournalController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockJournalFacade::getAll();

        return new StockJournalCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockJournalFacade::getById($id);

        return new StockJournalResource($data);
    }

    public function store(StockJournalRequest $request): SuccessResource
    {
        $data = StockJournalFacade::store($request->validated());

        return new StockJournalResource($data, $messages = 'StockJournal created successfully');
    }

    public function update(StockJournalRequest $request, int $id): SuccessResource
    {
        $data = StockJournalFacade::update($request->validated(), $id);

        return new StockJournalResource($data, $messages = 'StockJournal updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockJournalFacade::delete($id), 'StockJournal');
    }
}
