<?php

namespace Modules\Receipt\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Receipt\Contracts\ReceiptServiceInterface;
use Modules\Receipt\Resources\ReceiptResource;
use Modules\Receipt\Resources\ReceiptCollection;
use Modules\Receipt\Requests\ReceiptRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SuccessCollection;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ReceiptController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ReceiptServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();
        return new ReceiptCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);
        return  new ReceiptResource($data);
    }

    public function store(ReceiptRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
       return  new ReceiptResource($data, $messages='Receipt created successfully');
    }

    public function update(ReceiptRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);
        return  new ReceiptResource($data, $messages='Receipt updated successfully');
    }

        public function destroy(int $id): JsonResponse
    {

        $result=$this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result?'Receipt deleted successfully':'Receipt not found',
        ]);
    }
}
