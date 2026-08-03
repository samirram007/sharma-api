<?php

namespace Modules\OpeningBalance\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Modules\OpeningBalance\Facades\OpeningBalanceFacade;
use Modules\OpeningBalance\Requests\StoreOpeningBalanceRequest;
use Modules\OpeningBalance\Resources\OpeningBalanceSetupResource;

class OpeningBalanceController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    /**
     * Get setup data for the opening balance wizard.
     * Returns balance sheet ledgers & stock items, pre-filled from previous FY if closed.
     */
    public function setupData(): OpeningBalanceSetupResource
    {
        $data = OpeningBalanceFacade::getSetupData();

        return new OpeningBalanceSetupResource($data, 'Opening balance setup data retrieved successfully.');
    }

    /**
     * Create/Store opening balance entries.
     * Creates an OPNJL voucher with ledger entries and/or stock journal entries.
     */
    public function store(StoreOpeningBalanceRequest $request): SuccessResource
    {
        $data = OpeningBalanceFacade::storeOpeningBalance($request->validated());

        return new SuccessResource($data, $data['message'] ?? 'Opening balance created successfully.');
    }

    /**
     * Check if opening balance already exists for the current fiscal year.
     */
    public function status(): SuccessResource
    {
        $data = OpeningBalanceFacade::getStatus();

        return new SuccessResource($data, 'Opening balance status retrieved.');
    }
}
