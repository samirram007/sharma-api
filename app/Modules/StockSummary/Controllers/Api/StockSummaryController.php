<?php

namespace Modules\StockSummary\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;
use Modules\StockSummary\Resources\StockInHandCollection;
use Modules\StockSummary\Resources\StockInHandGodownResource;
use Modules\StockSummary\Resources\StockInHandGodownWiseResource;
use Modules\StockSummary\Resources\StockInHandItemDetailsResource;
use Modules\StockSummary\Resources\StockInHandItemWiseResource;
use Modules\StockSummary\Resources\StockInHandResource;
use Modules\StockSummary\Resources\StockInHandVoucherWiseResource;
use Modules\StockSummary\Resources\StockInHandZoneWiseResource;
use Modules\StockSummary\Resources\StockSummaryResource;
use Modules\StockSummary\Resources\StockSummaryCollection;
use Modules\StockSummary\Requests\StockSummaryRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class StockSummaryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StockSummaryServiceInterface $service)
    {
    }

    public function stock_in_hand(): StockInHandCollection
    {
        $data = $this->service->stockInHand();
        // dd($data);
        return new StockInHandCollection($data);
    }
    public function stock_in_hand_item_wise(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_item_wise();
        return StockInHandItemWiseResource::collection($data);
    }
    public function stock_in_hand_zone_wise(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_zone_wise();
        // dd($data);
        return StockInHandZoneWiseResource::collection($data);
    }
    public function stock_in_hand_godown_wise(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_godown_wise();
        // dd($data);
        return StockInHandGodownWiseResource::collection($data);
    }
    public function stock_in_hand_voucher_wise(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|array
    {
        $data = $this->service->stock_in_hand_voucher_wise();
        // dd($data);
        return StockInHandVoucherWiseResource::collection($data);
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
