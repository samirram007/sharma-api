<?php

namespace Modules\DistributorBook\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\DistributorBook\Facades\DistributorBookFacade;
use Modules\DistributorBook\Requests\DistributorBookRequest;
use Modules\DistributorBook\Resources\DistributorBookCollection;
use Modules\DistributorBook\Resources\DistributorBookResource;
use Modules\Voucher\Resources\VoucherCollection;

class DistributorBookController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = DistributorBookFacade::getAll();

        return new DistributorBookCollection($data);
    }

    public function show(int $id): SuccessCollection
    {
        $data = DistributorBookFacade::getById($id);

        return new VoucherCollection($data);
    }

    public function store(DistributorBookRequest $request): SuccessResource
    {
        $data = DistributorBookFacade::store($request->validated());

        return new DistributorBookResource($data, $messages = 'DistributorBook created successfully');
    }

    public function update(DistributorBookRequest $request, int $id): SuccessResource
    {
        $data = DistributorBookFacade::update($request->validated(), $id);

        return new DistributorBookResource($data, $messages = 'DistributorBook updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(DistributorBookFacade::delete($id), 'DistributorBook');
    }
}
