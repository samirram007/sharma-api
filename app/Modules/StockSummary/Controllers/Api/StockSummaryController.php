<?php

namespace Modules\StockSummary\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Modules\StockSummary\Facades\StockSummaryFacade;
use Modules\StockSummary\Requests\StockSummaryRequest;
use Modules\StockSummary\Resources\ClosingStockResource;
use Modules\StockSummary\Resources\StockInHandCollection;
use Modules\StockSummary\Resources\StockInHandGodownWiseResource;
use Modules\StockSummary\Resources\StockInHandItemWiseResource;
use Modules\StockSummary\Resources\StockInHandVoucherWiseResource;
use Modules\StockSummary\Resources\StockInHandZoneWiseResource;
use Modules\StockSummary\Resources\StockSummaryResource;

class StockSummaryController extends Controller
{
    use ApiResponseTrait;

    public function closing_stock(): ClosingStockResource
    {
        $data = StockSummaryFacade::closingStock();

        return new ClosingStockResource($data, 'Closing stock retrieved successfully.');
    }

    public function stock_in_hand(): StockInHandCollection
    {
        $data = StockSummaryFacade::stockInHand();

        // dd($data);
        return new StockInHandCollection($data);
    }

    public function stock_in_hand_item_wise(): AnonymousResourceCollection|array
    {
        $data = StockSummaryFacade::stock_in_hand_item_wise();

        return StockInHandItemWiseResource::collection($data);
    }

    public function stock_in_hand_zone_wise(): AnonymousResourceCollection|array
    {
        $data = StockSummaryFacade::stock_in_hand_zone_wise();

        // dd($data);
        return StockInHandZoneWiseResource::collection($data);
    }

    public function stock_in_hand_godown_wise(): AnonymousResourceCollection|array
    {
        $data = StockSummaryFacade::stock_in_hand_godown_wise();

        // dd($data);
        return StockInHandGodownWiseResource::collection($data);
    }

    public function stock_in_hand_voucher_wise(): AnonymousResourceCollection|array
    {
        $data = StockSummaryFacade::stock_in_hand_voucher_wise();

        // dd($data);
        return StockInHandVoucherWiseResource::collection($data);
    }

    public function runningBalanceItems(): SuccessCollection
    {
        $data = StockSummaryFacade::getRunningBalanceItems();

        return new SuccessCollection($data, 'Running balance items retrieved successfully.');
    }

    public function runningBalanceDetail(int $itemId): SuccessResource
    {
        $godownId = request()->integer('godown_id');
        $data = StockSummaryFacade::getRunningBalance($itemId, $godownId ?: null);

        return new SuccessResource($data, 'Running balance details retrieved successfully.');
    }

    public function runningBalanceGodowns(): SuccessCollection
    {
        $data = StockSummaryFacade::getRunningBalanceGodowns();

        return new SuccessCollection($data, 'Running balance godowns retrieved successfully.');
    }

    public function godownRunningBalanceItems(int $godownId): SuccessResource
    {
        $data = StockSummaryFacade::getGodownRunningBalanceItems($godownId);

        return new SuccessResource($data, 'Godown running balance items retrieved successfully.');
    }

    public function opening_stock(): SuccessResource
    {
        $godownId = request()->integer('godown_id') ?: null;
        $itemId = request()->integer('item_id') ?: null;
        $data = StockSummaryFacade::getOpeningStockReport($godownId, $itemId);

        return new SuccessResource($data, 'Opening stock report retrieved successfully.');
    }

    public function batch_list(): SuccessCollection
    {
        $search = request()->string('search')->toString() ?: null;
        $itemId = request()->integer('item_id') ?: null;
        $godownId = request()->integer('godown_id') ?: null;
        $data = StockSummaryFacade::getBatchList($search, 200, $itemId, $godownId);

        return new SuccessCollection($data, 'Batch list retrieved successfully.');
    }

    public function batch_movements(): SuccessResource
    {
        $filters = request()->only([
            'search',
            'item_id',
            'godown_id',
            'batch_no',
            'voucher_type_id',
            'from_date',
            'to_date',
            'expiry_status',
            'expiry_within_days',
            'in_stock_only',
            'issue',
            'sort_by',
            'sort_dir',
            'page',
            'per_page',
            'include_movements',
        ]);
        $data = StockSummaryFacade::getBatchMovements($filters);

        return new SuccessResource($data, 'Batch movements retrieved successfully.');
    }

    public function batch_lifeline(): SuccessResource
    {
        $filters = request()->only([
            'batch_no',
            'item_id',
            'godown_id',
            'from_date',
            'to_date',
            'expiry_within_days',
            'limit',
            'include_snapshots',
        ]);

        // A lifeline is always *one* batch — without a batch number the query
        // would be an unbounded dump of the whole ledger.
        if (trim((string) ($filters['batch_no'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'batch_no' => 'The batch no field is required for a batch lifeline.',
            ]);
        }

        $data = StockSummaryFacade::getBatchLifeline($filters);

        return new SuccessResource($data, 'Batch lifeline retrieved successfully.');
    }

    public function missing_opening_voucher_batches(): SuccessResource
    {
        $filters = request()->only(['item_id', 'godown_id', 'batch_no']);
        $data = StockSummaryFacade::getMissingOpeningVoucherBatches($filters);

        return new SuccessResource($data, 'Missing opening voucher batches retrieved successfully.');
    }

    public function net_stock(StockSummaryRequest $request): SuccessResource
    {
        $data = StockSummaryFacade::netStock($request->validated());

        return new StockSummaryResource($data);
    }

    public function purchase_order_outstanding(): SuccessResource
    {
        $data = StockSummaryFacade::purchaseOrderOutstanding();

        return new StockSummaryResource($data);
    }

    public function saleble_stock(): SuccessResource
    {
        $data = StockSummaryFacade::salebleStock();

        return new StockSummaryResource($data);
    }

    public function sales_order_outstanding(): SuccessResource
    {
        $data = StockSummaryFacade::salesOrderOutstanding();

        return new StockSummaryResource($data);
    }
}
