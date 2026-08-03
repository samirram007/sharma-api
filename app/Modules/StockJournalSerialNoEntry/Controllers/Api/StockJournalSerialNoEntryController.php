<?php

namespace Modules\StockJournalSerialNoEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalSerialNoEntry\Facades\StockJournalSerialNoEntryFacade;
use Modules\StockJournalSerialNoEntry\Requests\StockJournalSerialNoEntryRequest;
use Modules\StockJournalSerialNoEntry\Resources\StockJournalSerialNoEntryCollection;
use Modules\StockJournalSerialNoEntry\Resources\StockJournalSerialNoEntryResource;

class StockJournalSerialNoEntryController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = StockJournalSerialNoEntryFacade::getAll();

        return new StockJournalSerialNoEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = StockJournalSerialNoEntryFacade::getById($id);

        return new StockJournalSerialNoEntryResource($data);
    }

    public function store(StockJournalSerialNoEntryRequest $request): SuccessResource
    {
        $data = StockJournalSerialNoEntryFacade::store($request->validated());

        return new StockJournalSerialNoEntryResource($data, $messages = 'StockJournalSerialNoEntry created successfully');
    }

    public function update(StockJournalSerialNoEntryRequest $request, int $id): SuccessResource
    {
        $data = StockJournalSerialNoEntryFacade::update($request->validated(), $id);

        return new StockJournalSerialNoEntryResource($data, $messages = 'StockJournalSerialNoEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(StockJournalSerialNoEntryFacade::delete($id), 'StockJournalSerialNoEntry');
    }
}
