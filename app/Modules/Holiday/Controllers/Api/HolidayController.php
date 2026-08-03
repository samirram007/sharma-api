<?php

namespace Modules\Holiday\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Holiday\Facades\HolidayFacade;
use Modules\Holiday\Requests\HolidayRequest;
use Modules\Holiday\Resources\HolidayCollection;
use Modules\Holiday\Resources\HolidayResource;

class HolidayController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = HolidayFacade::getAll();

        return new HolidayCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = HolidayFacade::getById($id);

        return new HolidayResource($data);
    }

    public function store(HolidayRequest $request): SuccessResource
    {
        $data = HolidayFacade::store($request->validated());

        return new HolidayResource($data, $messages = 'Holiday created successfully');
    }

    public function update(HolidayRequest $request, int $id): SuccessResource
    {
        $data = HolidayFacade::update($request->validated(), $id);

        return new HolidayResource($data, $messages = 'Holiday updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(HolidayFacade::delete($id), 'Holiday');
    }
}
