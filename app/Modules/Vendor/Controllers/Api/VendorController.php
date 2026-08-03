<?php

namespace Modules\Vendor\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Vendor\Facades\VendorFacade;
use Modules\Vendor\Requests\VendorRequest;
use Modules\Vendor\Resources\VendorCollection;
use Modules\Vendor\Resources\VendorResource;

class VendorController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = VendorFacade::getAll();

        return new VendorCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = VendorFacade::getById($id);

        return new VendorResource($data);
    }

    public function store(VendorRequest $request): SuccessResource
    {
        $data = VendorFacade::store($request->validated());

        return new VendorResource($data, $messages = 'Vendor created successfully');
    }

    public function update(VendorRequest $request, int $id): SuccessResource
    {
        $data = VendorFacade::update($request->validated(), $id);

        return new VendorResource($data, $messages = 'Vendor updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(VendorFacade::delete($id), 'Vendor');
    }
}
