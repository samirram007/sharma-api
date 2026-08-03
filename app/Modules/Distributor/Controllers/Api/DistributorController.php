<?php

namespace Modules\Distributor\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Distributor\Facades\DistributorFacade;
use Modules\Distributor\Requests\DistributorRequest;
use Modules\Distributor\Resources\DistributorCollection;
use Modules\Distributor\Resources\DistributorResource;

class DistributorController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = DistributorFacade::getAll();

        return new DistributorCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = DistributorFacade::getById($id);

        return new DistributorResource($data);
    }

    public function store(DistributorRequest $request): SuccessResource
    {
        $data = DistributorFacade::store($request->validated());

        return new DistributorResource($data, $messages = 'Distributor created successfully');
    }

    public function update(DistributorRequest $request, int $id): SuccessResource
    {
        // dd($request->validated());
        $data = DistributorFacade::update($request->validated(), $id);

        return new DistributorResource($data, $messages = 'Distributor updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(DistributorFacade::delete($id), 'Distributor');
    }
}
