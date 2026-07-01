<?php

namespace Modules\Uqc\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Uqc\Contracts\UqcServiceInterface;
use Modules\Uqc\Resources\UqcResource;
use Modules\Uqc\Resources\UqcCollection;
use Modules\Uqc\Requests\UqcRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class UqcController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected UqcServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();
        return new UqcCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return  new UqcResource($data);
    }

    public function store(UqcRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
       return  new UqcResource($data, $messages='Uqc created successfully');
    }

    public function update(UqcRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return  new UqcResource($data, $messages='Uqc updated successfully');
    }

        public function destroy(int $id): JsonResponse
    {

        $result=$this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result?'Uqc deleted successfully':'Uqc not found',
        ]);
    }
}
