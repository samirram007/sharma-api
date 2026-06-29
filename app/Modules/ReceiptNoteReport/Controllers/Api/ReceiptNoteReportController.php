<?php

namespace App\Modules\ReceiptNoteReport\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;

use App\Modules\ReceiptNoteReport\Services\ReceiptNoteReportService;
use App\Modules\Voucher\Resources\VoucherCollection;

use Illuminate\Http\Request;

class ReceiptNoteReportController extends Controller
{
    public function __construct(protected ReceiptNoteReportService $service)
    {
    }

    public function index(Request $request): SuccessCollection
    {
        $params = $request->only([
            'search', 'sort_by', 'sort_order',
            'page', 'per_page'
        ]);
        $data = $this->service->getAll($params);
        return new VoucherCollection($data);
    }

    public function groupedByLedger(Request $request): SuccessCollection
    {
        $params = $request->only(['from_date', 'to_date']);
        $data = $this->service->getGroupedByLedger($params);
        return new SuccessCollection($data);
    }

    public function groupedByDate(Request $request): SuccessCollection
    {
        $params = $request->only(['from_date', 'to_date']);
        $data = $this->service->getGroupedByDate($params);
        return new SuccessCollection($data);
    }

    public function groupedByStockItem(Request $request): SuccessCollection
    {
        $params = $request->only(['from_date', 'to_date']);
        $data = $this->service->getGroupedByStockItem($params);
        return new SuccessCollection($data);
    }

    public function groupedByGodown(Request $request): SuccessCollection
    {
        $params = $request->only(['from_date', 'to_date']);
        $data = $this->service->getGroupedByGodown($params);
        return new SuccessCollection($data);
    }
}
