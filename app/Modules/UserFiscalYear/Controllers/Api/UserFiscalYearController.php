<?php

namespace Modules\UserFiscalYear\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\UserFiscalYear\Requests\ReportingPeriodRequest;
use Modules\UserFiscalYear\Requests\UserFiscalYearRequest;
use Modules\UserFiscalYear\Resources\UserFiscalYearCollection;
use Modules\UserFiscalYear\Resources\UserFiscalYearResource;

class UserFiscalYearController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected UserFiscalYearServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new UserFiscalYearCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new UserFiscalYearResource($data);
    }

    public function store(UserFiscalYearRequest $request): SuccessResource
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $userFiscalYear = $this->service->getAll()->where('user_id', $data['user_id'])->first();
        if ($userFiscalYear) {
            $data = $this->service->update($data, $userFiscalYear->id);

            return new UserFiscalYearResource($data, $messages = 'UserFiscalYear updated successfully');
        }
        $data = $this->service->store($data);

        return new UserFiscalYearResource($data, $messages = 'UserFiscalYear created successfully');
    }

    public function update(UserFiscalYearRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new UserFiscalYearResource($data, $messages = 'UserFiscalYear updated successfully');
    }

    public function saveReportingPeriod(ReportingPeriodRequest $request): SuccessResource
    {
        $data = $this->service->saveReportingPeriod($request->validated());

        return new UserFiscalYearResource($data, $messages = 'Reporting period saved successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'UserFiscalYear');
    }
}
