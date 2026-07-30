<?php

namespace Modules\StockJournalBatchEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryServiceInterface;
use Modules\StockJournalBatchEntry\Requests\StockJournalBatchEntryRequest;
use Modules\StockJournalBatchEntry\Resources\StockJournalBatchEntryCollection;
use Modules\StockJournalBatchEntry\Resources\StockJournalBatchEntryResource;

class StockJournalBatchEntryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockJournalBatchEntryServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockJournalBatchEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockJournalBatchEntryResource($data);
    }

    public function store(StockJournalBatchEntryRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockJournalBatchEntryResource($data, $messages = 'StockJournalBatchEntry created successfully');
    }

    public function update(StockJournalBatchEntryRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockJournalBatchEntryResource($data, $messages = 'StockJournalBatchEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockJournalBatchEntry');
    }
}
