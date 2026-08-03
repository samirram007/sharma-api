<?php

namespace Modules\VoucherNo\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\VoucherNo\Models\VoucherNo;
use Modules\VoucherNo\Requests\VoucherNoRequest;
use Modules\VoucherNo\Resources\VoucherNoCollection;
use Modules\VoucherNo\Resources\VoucherNoResource;
use Modules\VoucherType\Models\VoucherType;

class VoucherNoController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherNo::with([])->get();

        return new VoucherNoCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherNo::findOrFail($id);

        return new VoucherNoResource($data);
    }

    public function store(VoucherNoRequest $request): SuccessResource
    {
        $data = VoucherNo::create($request->validated());

        return new VoucherNoResource($data, $messages = 'VoucherNo created successfully');
    }

    public function update(VoucherNoRequest $request, int $id): SuccessResource
    {
        $record = VoucherNo::findOrFail($id);
        $record->update($request->validated());

        return new VoucherNoResource($record->fresh(), $messages = 'VoucherNo updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $record = VoucherNo::findOrFail($id);

        return $this->deletedResponse($record->delete(), 'VoucherNo');
    }

    /**
     * Generate the next voucher number with pessimistic locking.
     *
     * Uses DB::transaction with lockForUpdate() on the VoucherNo row to
     * prevent race conditions when called concurrently. This is the same
     * inline lockForUpdate() approach used in VoucherService's pipeline.
     */
    public function getVoucherNo(VoucherNoRequest $request): JsonResponse
    {
        $voucherTypeId = $request->input('voucher_type_id');
        $companyId = $request->input('company_id');
        $fiscalYearId = $request->input('fiscal_year_id');
        $branchId = $request->input('branch_id');

        $voucherNo = DB::transaction(function () use ($voucherTypeId, $companyId, $fiscalYearId, $branchId) {
            $voucherNoRecord = VoucherNo::where('voucher_type_id', $voucherTypeId)
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->lockForUpdate()
                ->first();

            if ($voucherNoRecord) {
                $voucherNoRecord->current_no += 1;
                $voucherNoRecord->save();
            } else {
                $voucherType = VoucherType::findOrFail($voucherTypeId);
                $prefix = substr($voucherType->code, 0, 4).'-';
                $voucherNoRecord = VoucherNo::create([
                    'prefix' => $prefix,
                    'voucher_type_id' => $voucherTypeId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId ?? null,
                    'fiscal_year_id' => $fiscalYearId,
                    'starting_no' => 1,
                    'current_no' => 1,
                ]);
            }

            return $voucherNoRecord->prefix.$voucherNoRecord->current_no;
        }, 5);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Voucher number retrieved successfully',
            'data' => [
                'voucher_no' => $voucherNo,
            ],
        ]);
    }
}
