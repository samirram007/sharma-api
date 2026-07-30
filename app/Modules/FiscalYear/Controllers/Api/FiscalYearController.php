<?php

namespace Modules\FiscalYear\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\FiscalYear\Contracts\FiscalYearServiceInterface;
use Modules\FiscalYear\Requests\FiscalYearRequest;
use Modules\FiscalYear\Resources\FiscalYearCollection;
use Modules\FiscalYear\Resources\FiscalYearResource;

class FiscalYearController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected FiscalYearServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();
        $resource = new FiscalYearCollection($data);

        // dd($resource);
        return $resource;
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new FiscalYearResource($data);
    }

    public function store(FiscalYearRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new FiscalYearResource($data, $messages = 'FiscalYear created successfully');
    }

    public function update(FiscalYearRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new FiscalYearResource($data, $messages = 'FiscalYear updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'FiscalYear');
    }
}
