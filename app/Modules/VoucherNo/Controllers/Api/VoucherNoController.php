<?php

namespace Modules\VoucherNo\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherNo\Contracts\VoucherNoServiceInterface;
use Modules\VoucherNo\Requests\VoucherNoRequest;
use Modules\VoucherNo\Resources\VoucherNoCollection;
use Modules\VoucherNo\Resources\VoucherNoResource;

class VoucherNoController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected VoucherNoServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new VoucherNoCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new VoucherNoResource($data);
    }

    public function store(VoucherNoRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new VoucherNoResource($data, $messages = 'VoucherNo created successfully');
    }

    public function update(VoucherNoRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new VoucherNoResource($data, $messages = 'VoucherNo updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'VoucherNo');
    }

    public function getVoucherNo(VoucherNoRequest $request): JsonResponse
    {
        $voucher_type_id = $request->input('voucher_type_id');
        $company_id = $request->input('company_id');
        $fiscal_year_id = $request->input('fiscal_year_id');
        $branch_id = $request->input('branch_id');

        $voucher_no = $this->service->getVoucherNo($voucher_type_id, $company_id, $fiscal_year_id, $branch_id);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Voucher number retrieved successfully',
            'data' => [
                'voucher_no' => $voucher_no,
            ],
        ]);
    }
}
