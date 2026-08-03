<?php

namespace Modules\Currency\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Currency\Facades\CurrencyFacade;
use Modules\Currency\Requests\CurrencyRequest;
use Modules\Currency\Resources\CurrencyCollection;
use Modules\Currency\Resources\CurrencyResource;

class CurrencyController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = CurrencyFacade::getAll();

        return (new CurrencyCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = CurrencyFacade::getById($id);

        return new CurrencyResource($data, $messages = 'Currency retrieved successfully');

    }

    public function store(CurrencyRequest $request): SuccessResource
    {
        $data = CurrencyFacade::store($request->validated());

        return new CurrencyResource($data, $messages = 'Currency created successfully');

    }

    public function update(CurrencyRequest $request, int $id): SuccessResource
    {
        $data = CurrencyFacade::update($request->validated(), $id);

        return new CurrencyResource($data, $messages = 'Currency updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(CurrencyFacade::delete($id), 'Currency');
    }
}
