<?php

namespace Modules\Currency\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Currency\Contracts\CurrencyServiceInterface;
use Modules\Currency\Resources\CurrencyResource;
use Modules\Currency\Resources\CurrencyCollection;
use Modules\Currency\Requests\CurrencyRequest;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected CurrencyServiceInterface $service)
    {
    }

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();
        return (new CurrencyCollection($data))->response();
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return new CurrencyResource($data, $messages = 'Currency retrieved successfully');


    }

    public function store(CurrencyRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
        return new CurrencyResource($data, $messages = 'Currency created successfully');

    }

    public function update(CurrencyRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return new CurrencyResource($data, $messages = 'Currency updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {

        $result = $this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result ? 'Currency deleted successfully' : 'Currency not found',
        ]);

    }
}
