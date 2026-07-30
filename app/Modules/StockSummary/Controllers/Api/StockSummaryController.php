<?php

namespace Modules\StockSummary\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;
use Modules\StockSummary\Requests\StockSummaryRequest;
use Modules\StockSummary\Resources\StockInHandCollection;
use Modules\StockSummary\Resources\StockInHandGodownWiseResource;
use Modules\StockSummary\Resources\StockInHandItemWiseResource;
use Modules\StockSummary\Resources\StockInHandVoucherWiseResource;
use Modules\StockSummary\Resources\StockInHandZoneWiseResource;
use Modules\StockSummary\Resources\StockSummaryResource;

class StockSummaryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockSummaryServiceInterface $service) {}

    public function stock_in_hand(): StockInHandCollection
    {
        $data = $this->service->stockInHand();

        // dd($data);
        return new StockInHandCollection($data);
    }

    public function stock_in_hand_item_wise(): AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_item_wise();

        return StockInHandItemWiseResource::collection($data);
    }

    public function stock_in_hand_zone_wise(): AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_zone_wise();

        // dd($data);
        return StockInHandZoneWiseResource::collection($data);
    }

    public function stock_in_hand_godown_wise(): AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_godown_wise();

        // dd($data);
        return StockInHandGodownWiseResource::collection($data);
    }

    public function stock_in_hand_voucher_wise(): AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_voucher_wise();

        // dd($data);
        return StockInHandVoucherWiseResource::collection($data);
    }

    public function runningBalanceItems(): SuccessCollection
    {
        $data = $this->service->getRunningBalanceItems();

        return new SuccessCollection($data, 'Running balance items retrieved successfully.');
    }

    public function runningBalanceDetail(int $itemId): SuccessResource
    {
        $godownId = request()->integer('godown_id');
        $data = $this->service->getRunningBalance($itemId, $godownId ?: null);

        return new SuccessResource($data, 'Running balance details retrieved successfully.');
    }

    public function runningBalanceGodowns(): SuccessCollection
    {
        $data = $this->service->getRunningBalanceGodowns();

        return new SuccessCollection($data, 'Running balance godowns retrieved successfully.');
    }

    public function godownRunningBalanceItems(int $godownId): SuccessResource
    {
        $data = $this->service->getGodownRunningBalanceItems($godownId);

        return new SuccessResource($data, 'Godown running balance items retrieved successfully.');
    }

    public function net_stock(StockSummaryRequest $request): SuccessResource
    {
        $data = $this->service->netStock($request->validated());

        return new StockSummaryResource($data);
    }

    public function purchase_order_outstanding(): SuccessResource
    {
        $data = $this->service->purchaseOrderOutstanding();

        return new StockSummaryResource($data);
    }

    public function saleble_stock(): SuccessResource
    {
        $data = $this->service->salebleStock();

        return new StockSummaryResource($data);
    }

    public function sales_order_outstanding(): SuccessResource
    {
        $data = $this->service->salesOrderOutstanding();

        return new StockSummaryResource($data);
    }
}
