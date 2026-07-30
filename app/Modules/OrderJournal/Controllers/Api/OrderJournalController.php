<?php

namespace Modules\OrderJournal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\OrderJournal\Contracts\OrderJournalServiceInterface;
use Modules\OrderJournal\Requests\OrderJournalRequest;
use Modules\OrderJournal\Resources\OrderJournalCollection;
use Modules\OrderJournal\Resources\OrderJournalResource;

class OrderJournalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected OrderJournalServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new OrderJournalCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new OrderJournalResource($data);
    }

    public function store(OrderJournalRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new OrderJournalResource($data, $messages = 'OrderJournal created successfully');
    }

    public function update(OrderJournalRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new OrderJournalResource($data, $messages = 'OrderJournal updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'OrderJournal');
    }
}
