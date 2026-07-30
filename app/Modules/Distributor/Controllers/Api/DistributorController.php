<?php

namespace Modules\Distributor\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Distributor\Contracts\DistributorServiceInterface;
use Modules\Distributor\Requests\DistributorRequest;
use Modules\Distributor\Resources\DistributorCollection;
use Modules\Distributor\Resources\DistributorResource;

class DistributorController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DistributorServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new DistributorCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DistributorResource($data);
    }

    public function store(DistributorRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DistributorResource($data, $messages = 'Distributor created successfully');
    }

    public function update(DistributorRequest $request, int $id): SuccessResource
    {
        // dd($request->validated());
        $data = $this->service->update($request->validated(), $id);

        return new DistributorResource($data, $messages = 'Distributor updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Distributor');
    }
}
