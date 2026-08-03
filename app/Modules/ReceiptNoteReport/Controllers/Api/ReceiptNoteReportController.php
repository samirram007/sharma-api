<?php

namespace Modules\ReceiptNoteReport\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use Illuminate\Http\Request;
use Modules\ReceiptNoteReport\Contracts\ReceiptNoteReportServiceInterface;
use Modules\Voucher\Resources\VoucherCollection;

class ReceiptNoteReportController extends Controller
{
    public function __construct(protected ReceiptNoteReportServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

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
