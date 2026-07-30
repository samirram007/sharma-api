<?php

namespace Modules\AccountLedger\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AccountLedger\Contracts\AccountLedgerServiceInterface;
use Modules\AccountLedger\Requests\AccountLedgerRequest;
use Modules\AccountLedger\Resources\AccountLedgerCollection;
use Modules\AccountLedger\Resources\AccountLedgerResource;
use Modules\AccountLedger\Resources\LedgerBalanceResource;

class AccountLedgerController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AccountLedgerServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new AccountLedgerCollection($data);
    }

    public function show(int $id): ?SuccessResource
    {
        $data = $this->service->getById($id);

        return
            new AccountLedgerResource(
                $data,
                $message = 'AccountLedger retrieved successfully'
            );

    }

    public function store(AccountLedgerRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return
            new AccountLedgerResource(
                $data,
                $message = 'AccountLedger created successfully',
            );
    }

    public function update(AccountLedgerRequest $request, int $id): SuccessResource
    {
        // dd($request->all());

        $data = $this->service->update($request->validated(), $id);

        return new AccountLedgerResource($data, $message = 'AccountLedger updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return $this->deletedResponse($result, 'AccountLedger');
    }

    public function ledger_balance(int $id): ?SuccessResource
    {
        $data = $this->service->getLedgerBalance($id);

        // dd($data);
        return
            new LedgerBalanceResource(
                (object) $data,
                'AccountLedger Balance retrieved successfully'
            );

    }

    public function purchase_ledgers(): SuccessCollection
    {
        $data = $this->service->getPurchaseLedgers();

        return new AccountLedgerCollection($data);
    }

    public function sale_ledgers(): SuccessCollection
    {
        $data = $this->service->getSaleLedgers();

        return new AccountLedgerCollection($data);
    }

    public function supplier_ledgers(): SuccessCollection
    {
        $data = $this->service->getSupplierLedgers();

        return new AccountLedgerCollection($data);
    }

    public function distributor_ledgers(): SuccessCollection
    {
        $data = $this->service->getDistributorLedgers();

        return new AccountLedgerCollection($data);
    }

    public function stock_in_hand_ledgers(): SuccessCollection
    {
        $data = $this->service->getStockInHandLedgers();

        return new AccountLedgerCollection($data);
    }
}
