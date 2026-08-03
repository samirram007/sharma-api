<?php

namespace Modules\FiscalYear\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\FiscalYear\Facades\FiscalYearFacade;
use Modules\FiscalYear\Requests\FiscalYearRequest;
use Modules\FiscalYear\Resources\FiscalYearCollection;
use Modules\FiscalYear\Resources\FiscalYearResource;

class FiscalYearController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = FiscalYearFacade::getAll();

        return new FiscalYearCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = FiscalYearFacade::getById($id);

        return new FiscalYearResource($data);
    }

    public function store(FiscalYearRequest $request): SuccessResource
    {
        $data = FiscalYearFacade::store($request->validated());

        return new FiscalYearResource($data, $messages = 'FiscalYear created successfully');
    }

    public function update(FiscalYearRequest $request, int $id): SuccessResource
    {
        $data = FiscalYearFacade::update($request->validated(), $id);

        return new FiscalYearResource($data, $messages = 'FiscalYear updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(FiscalYearFacade::delete($id), 'FiscalYear');
    }
}
