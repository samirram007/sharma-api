<?php

namespace Modules\DayBook\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\DayBook\Facades\DayBookFacade;
use Modules\DayBook\Requests\DayBookRequest;
use Modules\DayBook\Resources\DayBookResource;
use Modules\Voucher\Resources\VoucherCollection;
use Modules\VoucherType\Resources\VoucherTypeResource;

class DayBookController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    public function index(): SuccessCollection
    {
        $data = DayBookFacade::getAll();

        return new VoucherCollection($data);
    }

    public function dayBooksSelf(Request $request): SuccessCollection
    {
        $params = $request->only(['search', 'voucher_type_id', 'billing_preference', 'status', 'sort_by', 'sort_order', 'page', 'per_page']);
        $data = DayBookFacade::dayBooksSelf($params);

        return new VoucherCollection($data);
    }

    public function usedVoucherTypes(): JsonResponse
    {
        $types = DayBookFacade::getUsedVoucherTypes();

        return response()->json([
            'success' => true,
            'data' => VoucherTypeResource::collection($types),
        ]);
    }

    public function show(int $id): SuccessResource
    {
        $data = DayBookFacade::getById($id);

        return new DayBookResource($data);
    }

    public function store(DayBookRequest $request): SuccessResource
    {
        $data = DayBookFacade::store($request->validated());

        return new DayBookResource($data, $messages = 'DayBook created successfully');
    }

    public function update(DayBookRequest $request, int $id): SuccessResource
    {
        $data = DayBookFacade::update($request->validated(), $id);

        return new DayBookResource($data, $messages = 'DayBook updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(DayBookFacade::delete($id), 'DayBook');
    }
}
