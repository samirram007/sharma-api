<?php

namespace Modules\Country\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Country\Contracts\CountryServiceInterface;
use Modules\Country\Requests\CountryRequest;
use Modules\Country\Resources\CountryCollection;
use Modules\Country\Resources\CountryResource;

class CountryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected CountryServiceInterface $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();

        return (new CountryCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new CountryResource($data, $messages = 'Country retrieved successfully');

    }

    public function store(CountryRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new CountryResource($data, $messages = 'Country created successfully');

    }

    public function update(CountryRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new CountryResource($data, $messages = 'Country updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Country');
    }
}
