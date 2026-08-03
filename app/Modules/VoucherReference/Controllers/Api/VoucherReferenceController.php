<?php

namespace Modules\VoucherReference\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\VoucherReference\Facades\VoucherReferenceFacade;
use Modules\VoucherReference\Requests\VoucherReferenceRequest;
use Modules\VoucherReference\Resources\VoucherReferenceCollection;
use Modules\VoucherReference\Resources\VoucherReferenceResource;

class VoucherReferenceController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VoucherReferenceFacade::getAll();

        return new VoucherReferenceCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VoucherReferenceFacade::getById($id);

        return new VoucherReferenceResource($data);
    }

    public function store(VoucherReferenceRequest $request): SuccessResource
    {
        $data = VoucherReferenceFacade::store($request->validated());

        return new VoucherReferenceResource($data, $messages = 'VoucherReference created successfully');
    }

    public function update(VoucherReferenceRequest $request, int $id): SuccessResource
    {
        $data = VoucherReferenceFacade::update($request->validated(), $id);

        return new VoucherReferenceResource($data, $messages = 'VoucherReference updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VoucherReferenceFacade::delete($id), 'VoucherReference');
    }
}
