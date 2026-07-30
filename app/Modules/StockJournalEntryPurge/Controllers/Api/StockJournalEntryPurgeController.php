<?php

namespace Modules\StockJournalEntryPurge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeServiceInterface;
use Modules\StockJournalEntryPurge\Requests\StockJournalEntryPurgeRequest;
use Modules\StockJournalEntryPurge\Resources\StockJournalEntryPurgeCollection;
use Modules\StockJournalEntryPurge\Resources\StockJournalEntryPurgeResource;

class StockJournalEntryPurgeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockJournalEntryPurgeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockJournalEntryPurgeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockJournalEntryPurgeResource($data);
    }

    public function store(StockJournalEntryPurgeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockJournalEntryPurgeResource($data, $messages = 'StockJournalEntryPurge created successfully');
    }

    public function update(StockJournalEntryPurgeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockJournalEntryPurgeResource($data, $messages = 'StockJournalEntryPurge updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockJournalEntryPurge');
    }
}
