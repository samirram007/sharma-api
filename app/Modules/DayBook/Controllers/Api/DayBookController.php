<?php

namespace Modules\DayBook\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\DayBook\Contracts\DayBookServiceInterface;
use Modules\DayBook\Requests\DayBookRequest;
use Modules\DayBook\Resources\DayBookResource;
use Modules\Voucher\Resources\VoucherCollection;
use Modules\VoucherType\Resources\VoucherTypeResource;

class DayBookController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DayBookServiceInterface $service) {}

    public function index(Request $request): SuccessCollection
    {
        $params = $request->only(['search', 'voucher_type_id', 'billing_preference', 'status', 'sort_by', 'sort_order', 'page', 'per_page']);
        $data = $this->service->getAll($params);

        return new VoucherCollection($data);
    }

    public function dayBooksSelf(Request $request): SuccessCollection
    {
        $params = $request->only(['search', 'voucher_type_id', 'billing_preference', 'status', 'sort_by', 'sort_order', 'page', 'per_page']);
        $data = $this->service->dayBooksSelf($params);

        return new VoucherCollection($data);
    }

    public function usedVoucherTypes(): JsonResponse
    {
        $types = $this->service->getUsedVoucherTypes();

        return response()->json([
            'success' => true,
            'data' => VoucherTypeResource::collection($types),
        ]);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DayBookResource($data);
    }

    public function store(DayBookRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DayBookResource($data, $messages = 'DayBook created successfully');
    }

    public function update(DayBookRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new DayBookResource($data, $messages = 'DayBook updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'DayBook');
    }
}
