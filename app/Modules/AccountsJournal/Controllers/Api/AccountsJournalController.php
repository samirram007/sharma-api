<?php

namespace Modules\AccountsJournal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AccountsJournal\Contracts\AccountsJournalServiceInterface;
use Modules\AccountsJournal\Requests\AccountsJournalRequest;
use Modules\AccountsJournal\Resources\AccountsJournalCollection;
use Modules\AccountsJournal\Resources\AccountsJournalResource;

class AccountsJournalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AccountsJournalServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new AccountsJournalCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new AccountsJournalResource($data);
    }

    public function store(AccountsJournalRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new AccountsJournalResource($data, $messages = 'AccountsJournal created successfully');
    }

    public function update(AccountsJournalRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new AccountsJournalResource($data, $messages = 'AccountsJournal updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'AccountsJournal');
    }
}
