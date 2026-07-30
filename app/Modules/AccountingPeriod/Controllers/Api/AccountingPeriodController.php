<?php

namespace Modules\AccountingPeriod\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AccountingPeriod\Contracts\AccountingPeriodServiceInterface;
use Modules\AccountingPeriod\Requests\AccountingPeriodRequest;
use Modules\AccountingPeriod\Resources\AccountingPeriodCollection;
use Modules\AccountingPeriod\Resources\AccountingPeriodResource;

class AccountingPeriodController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AccountingPeriodServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new AccountingPeriodCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new AccountingPeriodResource($data);
    }

    public function store(AccountingPeriodRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new AccountingPeriodResource($data, $messages = 'AccountingPeriod created successfully');
    }

    public function update(AccountingPeriodRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new AccountingPeriodResource($data, $messages = 'AccountingPeriod updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'AccountingPeriod');
    }
}
