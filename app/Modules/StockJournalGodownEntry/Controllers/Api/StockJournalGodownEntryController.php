<?php

namespace Modules\StockJournalGodownEntry\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournalGodownEntry\Contracts\StockJournalGodownEntryServiceInterface;
use Modules\StockJournalGodownEntry\Requests\StockJournalGodownEntryRequest;
use Modules\StockJournalGodownEntry\Resources\StockJournalGodownEntryCollection;
use Modules\StockJournalGodownEntry\Resources\StockJournalGodownEntryResource;

class StockJournalGodownEntryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockJournalGodownEntryServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockJournalGodownEntryCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockJournalGodownEntryResource($data);
    }

    public function store(StockJournalGodownEntryRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockJournalGodownEntryResource($data, $messages = 'StockJournalGodownEntry created successfully');
    }

    public function update(StockJournalGodownEntryRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockJournalGodownEntryResource($data, $messages = 'StockJournalGodownEntry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockJournalGodownEntry');
    }
}
