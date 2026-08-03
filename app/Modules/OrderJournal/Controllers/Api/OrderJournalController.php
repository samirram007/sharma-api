<?php

namespace Modules\OrderJournal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\OrderJournal\Facades\OrderJournalFacade;
use Modules\OrderJournal\Requests\OrderJournalRequest;
use Modules\OrderJournal\Resources\OrderJournalCollection;
use Modules\OrderJournal\Resources\OrderJournalResource;

class OrderJournalController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = OrderJournalFacade::getAll();

        return new OrderJournalCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = OrderJournalFacade::getById($id);

        return new OrderJournalResource($data);
    }

    public function store(OrderJournalRequest $request): SuccessResource
    {
        $data = OrderJournalFacade::store($request->validated());

        return new OrderJournalResource($data, $messages = 'OrderJournal created successfully');
    }

    public function update(OrderJournalRequest $request, int $id): SuccessResource
    {
        $data = OrderJournalFacade::update($request->validated(), $id);

        return new OrderJournalResource($data, $messages = 'OrderJournal updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(OrderJournalFacade::delete($id), 'OrderJournal');
    }
}
