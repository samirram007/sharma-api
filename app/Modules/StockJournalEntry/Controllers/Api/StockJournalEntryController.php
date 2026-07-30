<?php

namespace Modules\StockJournalEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\StockJournalEntry\Requests\StockJournalEntryRequest;
use Modules\StockJournalEntry\Resources\StockJournalEntryCollection;
use Modules\StockJournalEntry\Resources\StockJournalEntryResource;

class StockJournalEntryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockJournalEntryServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockJournalEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockJournalEntryResource($data);
    }

    public function store(StockJournalEntryRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockJournalEntryResource($data, $messages = 'StockJournalEntry created successfully');
    }

    public function update(StockJournalEntryRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockJournalEntryResource($data, $messages = 'StockJournalEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockJournalEntry');
    }
}
