<?php

namespace Modules\OrderStockJournal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\OrderStockJournal\Contracts\OrderStockJournalServiceInterface;
use Modules\OrderStockJournal\Requests\OrderStockJournalRequest;
use Modules\OrderStockJournal\Resources\OrderStockJournalCollection;
use Modules\OrderStockJournal\Resources\OrderStockJournalResource;

class OrderStockJournalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected OrderStockJournalServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new OrderStockJournalCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new OrderStockJournalResource($data);
    }

    public function store(OrderStockJournalRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new OrderStockJournalResource($data, $messages = 'OrderStockJournal created successfully');
    }

    public function update(OrderStockJournalRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new OrderStockJournalResource($data, $messages = 'OrderStockJournal updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'OrderStockJournal');
    }
}
