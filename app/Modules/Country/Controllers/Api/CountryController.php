<?php

namespace Modules\Country\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Country\Facades\CountryFacade;
use Modules\Country\Requests\CountryRequest;
use Modules\Country\Resources\CountryCollection;
use Modules\Country\Resources\CountryResource;

class CountryController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = CountryFacade::getAll();

        return (new CountryCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = CountryFacade::getById($id);

        return new CountryResource($data, $messages = 'Country retrieved successfully');

    }

    public function store(CountryRequest $request): SuccessResource
    {
        $data = CountryFacade::store($request->validated());

        return new CountryResource($data, $messages = 'Country created successfully');

    }

    public function update(CountryRequest $request, int $id): SuccessResource
    {
        $data = CountryFacade::update($request->validated(), $id);

        return new CountryResource($data, $messages = 'Country updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(CountryFacade::delete($id), 'Country');
    }
}
