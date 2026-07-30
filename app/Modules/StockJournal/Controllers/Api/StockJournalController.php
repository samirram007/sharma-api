<?php

namespace Modules\StockJournal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournal\Requests\StockJournalRequest;
use Modules\StockJournal\Resources\StockJournalCollection;
use Modules\StockJournal\Resources\StockJournalResource;

class StockJournalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockJournalServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new StockJournalCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new StockJournalResource($data);
    }

    public function store(StockJournalRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new StockJournalResource($data, $messages = 'StockJournal created successfully');
    }

    public function update(StockJournalRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new StockJournalResource($data, $messages = 'StockJournal updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'StockJournal');
    }
}
