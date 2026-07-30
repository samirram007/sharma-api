<?php

namespace Modules\Transporter\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Transporter\Contracts\TransporterServiceInterface;
use Modules\Transporter\Requests\TransporterRequest;
use Modules\Transporter\Resources\TransporterCollection;
use Modules\Transporter\Resources\TransporterResource;

class TransporterController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected TransporterServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new TransporterCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new TransporterResource($data);
    }

    public function store(TransporterRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new TransporterResource($data, $messages = 'Transporter created successfully');
    }

    public function update(TransporterRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new TransporterResource($data, $messages = 'Transporter updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Transporter');
    }
}
