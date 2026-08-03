<?php

namespace Modules\AccountLedger\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AccountLedger\Facades\AccountLedgerFacade;
use Modules\AccountLedger\Requests\AccountLedgerRequest;
use Modules\AccountLedger\Resources\AccountLedgerCollection;
use Modules\AccountLedger\Resources\AccountLedgerResource;
use Modules\AccountLedger\Resources\LedgerBalanceResource;

class AccountLedgerController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = AccountLedgerFacade::getAll();

        return new AccountLedgerCollection($data);
    }

    public function show(int $id): ?SuccessResource
    {
        $data = AccountLedgerFacade::getById($id);

        return
            new AccountLedgerResource(
                $data,
                $message = 'AccountLedger retrieved successfully'
            );

    }

    public function store(AccountLedgerRequest $request): SuccessResource
    {
        $data = AccountLedgerFacade::store($request->validated());

        return
            new AccountLedgerResource(
                $data,
                $message = 'AccountLedger created successfully',
            );
    }

    public function update(AccountLedgerRequest $request, int $id): SuccessResource
    {
        // dd($request->all());

        $data = AccountLedgerFacade::update($request->validated(), $id);

        return new AccountLedgerResource($data, $message = 'AccountLedger updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        $result = AccountLedgerFacade::delete($id);

        return $this->deletedResponse($result, 'AccountLedger');
    }

    public function ledger_balance(int $id): ?SuccessResource
    {
        $data = AccountLedgerFacade::getLedgerBalance($id);

        // dd($data);
        return
            new LedgerBalanceResource(
                (object) $data,
                'AccountLedger Balance retrieved successfully'
            );

    }

    public function purchase_ledgers(): SuccessCollection
    {
        $data = AccountLedgerFacade::getPurchaseLedgers();

        return new AccountLedgerCollection($data);
    }

    public function sale_ledgers(): SuccessCollection
    {
        $data = AccountLedgerFacade::getSaleLedgers();

        return new AccountLedgerCollection($data);
    }

    public function supplier_ledgers(): SuccessCollection
    {
        $data = AccountLedgerFacade::getSupplierLedgers();

        return new AccountLedgerCollection($data);
    }

    public function distributor_ledgers(): SuccessCollection
    {
        $data = AccountLedgerFacade::getDistributorLedgers();

        return new AccountLedgerCollection($data);
    }

    public function stock_in_hand_ledgers(): SuccessCollection
    {
        $data = AccountLedgerFacade::getStockInHandLedgers();

        return new AccountLedgerCollection($data);
    }
}
