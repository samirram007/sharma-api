<?php

namespace Modules\StockJournalGodownEntryPurge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeServiceInterface;
use Modules\StockJournalGodownEntryPurge\Requests\StockJournalGodownEntryPurgeRequest;
use Modules\StockJournalGodownEntryPurge\Resources\StockJournalGodownEntryPurgeCollection;
use Modules\StockJournalGodownEntryPurge\Resources\StockJournalGodownEntryPurgeResource;

class StockJournalGodownEntryPurgeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockJournalGodownEntryPurgeServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockJournalGodownEntryPurgeCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockJournalGodownEntryPurgeResource($data);
    }

    public function store(StockJournalGodownEntryPurgeRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockJournalGodownEntryPurgeResource($data, $messages = 'StockJournalGodownEntryPurge created successfully');
    }

    public function update(StockJournalGodownEntryPurgeRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockJournalGodownEntryPurgeResource($data, $messages = 'StockJournalGodownEntryPurge updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockJournalGodownEntryPurge');
    }
}
