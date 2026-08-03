<?php

namespace Modules\VoucherParty\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherParty\Facades\VoucherPartyFacade;
use Modules\VoucherParty\Requests\VoucherPartyRequest;
use Modules\VoucherParty\Resources\VoucherPartyCollection;
use Modules\VoucherParty\Resources\VoucherPartyResource;

class VoucherPartyController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherPartyFacade::getAll();

        return new VoucherPartyCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherPartyFacade::getById($id);

        return new VoucherPartyResource($data);
    }

    public function store(VoucherPartyRequest $request): SuccessResource
    {
        $data = VoucherPartyFacade::store($request->validated());

        return new VoucherPartyResource($data, $messages = 'VoucherParty created successfully');
    }

    public function update(VoucherPartyRequest $request, int $id): SuccessResource
    {
        $data = VoucherPartyFacade::update($request->validated(), $id);

        return new VoucherPartyResource($data, $messages = 'VoucherParty updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherPartyFacade::delete($id), 'VoucherParty');
    }
}
