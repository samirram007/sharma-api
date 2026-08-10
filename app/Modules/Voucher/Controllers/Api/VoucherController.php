<?php

namespace Modules\Voucher\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Voucher\Facades\VoucherFacade;
use Modules\Voucher\Requests\VoucherRequest;
use Modules\Voucher\Resources\VoucherCollection;
use Modules\Voucher\Resources\VoucherPrintResource;
use Modules\Voucher\Resources\VoucherResource;

class VoucherController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    public function index(Request $request): SuccessCollection
    {
        // Optional server-side filtering — the Opening Stock screen passes the
        // resolved OPNSK voucher type id so only its (one-per-FY) vouchers are
        // loaded instead of the entire voucher table with every deep relation
        // (which exhausts PHP memory on large datasets).
        //
        // Entry lists are isolated per fiscal year: without an explicit
        // fiscal_year_id the services default to the user's assigned fiscal
        // year; an explicit id (e.g. cross-FY lookups) overrides it.
        $fiscalYearId = $request->filled('fiscal_year_id')
            ? (int) $request->query('fiscal_year_id')
            : null;

        if ($request->filled('voucher_type_id')) {
            $data = VoucherFacade::getByVoucherType(
                (int) $request->query('voucher_type_id'),
                $fiscalYearId
            );
        } elseif ($request->filled('module')) {
            $data = VoucherFacade::getByModule((string) $request->query('module'), $fiscalYearId);
        } else {
            $data = VoucherFacade::getAll($fiscalYearId);
        }

        return new VoucherCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherFacade::getById($id);

        return new VoucherResource($data);
    }

    public function store(VoucherRequest $request): SuccessResource
    {
        $data = VoucherFacade::store($request->validated());

        return new VoucherResource($data, $messages = 'Voucher created successfully');
    }

    public function update(VoucherRequest $request, int $id): SuccessResource
    {
        $data = VoucherFacade::update($request->validated(), $id);

        return new VoucherResource($data, $messages = 'Voucher updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherFacade::delete($id), 'Voucher');
    }

    public function print(int $id): SuccessResource
    {
        $data = VoucherFacade::getById($id);

        return new VoucherPrintResource($data);
    }

    public function previousYearClosingStock(): SuccessResource
    {
        $data = VoucherFacade::getPreviousYearClosingStock();

        return new SuccessResource($data, 'Previous year closing stock retrieved successfully.');
    }

    public function openingStockVoucherType(): SuccessResource
    {
        $data = VoucherFacade::getOpeningStockVoucherType();

        return new SuccessResource($data, 'Opening stock voucher type retrieved successfully.');
    }
}
