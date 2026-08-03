<?php

namespace Modules\ConversionJournalReport\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use Illuminate\Http\Request;
use Modules\ConversionJournalReport\Contracts\ConversionJournalReportServiceInterface;
use Modules\Voucher\Resources\VoucherCollection;

class ConversionJournalReportController extends Controller
{
    public function __construct(protected ConversionJournalReportServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new VoucherCollection($data);
    }

    public function groupedByStockItem(Request $request): SuccessCollection
    {
        $params = $request->only(['from_date', 'to_date', 'stock_journal_type']);
        $data = $this->service->getGroupedByStockItem($params);

        return new SuccessCollection($data);
    }

    public function groupedByGodown(Request $request): SuccessCollection
    {
        $params = $request->only(['from_date', 'to_date', 'stock_journal_type']);
        $data = $this->service->getGroupedByGodown($params);

        return new SuccessCollection($data);
    }

    public function groupedByDate(Request $request): SuccessCollection
    {
        $params = $request->only(['from_date', 'to_date', 'stock_journal_type']);
        $data = $this->service->getGroupedByDate($params);

        return new SuccessCollection($data);
    }
}
