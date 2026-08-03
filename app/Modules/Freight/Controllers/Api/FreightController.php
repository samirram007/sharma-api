<?php

namespace Modules\Freight\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Freight\Facades\FreightFacade;
use Modules\Freight\Requests\FreightRequest;
use Modules\Freight\Resources\FreightCollection;
use Modules\Freight\Resources\FreightResource;
use Modules\Voucher\Resources\VoucherCollection;
use Modules\Voucher\Resources\VoucherResource;

class FreightController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    public function index(): SuccessCollection
    {
        $data = FreightFacade::getAll();

        return new FreightCollection($data);
    }

    public function delivery_note(Request $request): SuccessCollection
    {
        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);
        $filters = $request->only(['search', 'freight_status']);
        $data = FreightFacade::getDeliveryNote($page, $perPage, $filters);
        $overallTotalFare = FreightFacade::getDeliveryNoteOverallTotalFare($filters);

        return (new VoucherCollection($data))->additional([
            'aggregates' => [
                'total_fare' => $overallTotalFare,
            ],
        ]);
    }

    public function godown_wise(): SuccessCollection
    {
        $data = FreightFacade::godownWiseReport();

        return new SuccessCollection($data);
    }

    public function zone_wise(): SuccessCollection
    {
        $data = FreightFacade::zoneWiseReport();

        return new SuccessCollection($data);
    }

    public function delivery_note_zone_wise(): SuccessCollection
    {
        $data = FreightFacade::deliveryNoteZoneWiseReport();

        return new SuccessCollection($data);
    }

    public function delivery_note_godown_wise(Request $request): SuccessCollection
    {
        $zoneId = $request->integer('zone_id');
        $godownId = $request->integer('godown_id');
        $data = FreightFacade::deliveryNoteGodownWiseReport(
            $zoneId ?: null,
            $godownId ?: null
        );

        return new SuccessCollection($data);
    }

    public function transporter_wise(): SuccessCollection
    {
        $data = FreightFacade::transporterWiseReport();

        return new VoucherCollection($data);
    }

    public function transporter_item_wise(): SuccessCollection
    {
        $data = FreightFacade::transporterItemWiseReport();

        return new SuccessCollection($data);
    }

    public function vehicle_wise(): SuccessCollection
    {
        $data = FreightFacade::vehicleWiseReport();

        return new VoucherCollection($data);
    }

    public function voucher_wise(): SuccessCollection
    {
        $data = FreightFacade::voucherWiseReport();

        return new VoucherCollection($data);
    }

    public function billing_preference(): SuccessCollection
    {
        $data = FreightFacade::billingPreferenceReport();

        return new SuccessCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = FreightFacade::getById($id);

        return new FreightResource($data);
    }

    public function store(FreightRequest $request): SuccessResource
    {
        $data = FreightFacade::store($request->validated());

        return new VoucherResource($data, $messages = 'Freight created successfully');
    }

    public function update(FreightRequest $request, int $id): SuccessResource
    {
        $data = FreightFacade::update($request->validated(), $id);

        return new FreightResource($data, $messages = 'Freight updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(FreightFacade::delete($id), 'Freight');
    }
}
