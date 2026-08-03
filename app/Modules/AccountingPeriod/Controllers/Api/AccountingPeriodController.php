<?php

namespace Modules\AccountingPeriod\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AccountingPeriod\Facades\AccountingPeriodFacade;
use Modules\AccountingPeriod\Requests\AccountingPeriodRequest;
use Modules\AccountingPeriod\Resources\AccountingPeriodCollection;
use Modules\AccountingPeriod\Resources\AccountingPeriodResource;

class AccountingPeriodController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = AccountingPeriodFacade::getAll();

        return new AccountingPeriodCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = AccountingPeriodFacade::getById($id);

        return new AccountingPeriodResource($data);
    }

    public function store(AccountingPeriodRequest $request): SuccessResource
    {
        $data = AccountingPeriodFacade::store($request->validated());

        return new AccountingPeriodResource($data, $messages = 'AccountingPeriod created successfully');
    }

    public function update(AccountingPeriodRequest $request, int $id): SuccessResource
    {
        $data = AccountingPeriodFacade::update($request->validated(), $id);

        return new AccountingPeriodResource($data, $messages = 'AccountingPeriod updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(AccountingPeriodFacade::delete($id), 'AccountingPeriod');
    }
}
