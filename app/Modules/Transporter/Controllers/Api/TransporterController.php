<?php

namespace Modules\Transporter\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Transporter\Facades\TransporterFacade;
use Modules\Transporter\Requests\TransporterRequest;
use Modules\Transporter\Resources\TransporterCollection;
use Modules\Transporter\Resources\TransporterResource;

class TransporterController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = TransporterFacade::getAll();

        return new TransporterCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = TransporterFacade::getById($id);

        return new TransporterResource($data);
    }

    public function store(TransporterRequest $request): SuccessResource
    {
        $data = TransporterFacade::store($request->validated());

        return new TransporterResource($data, $messages = 'Transporter created successfully');
    }

    public function update(TransporterRequest $request, int $id): SuccessResource
    {
        $data = TransporterFacade::update($request->validated(), $id);

        return new TransporterResource($data, $messages = 'Transporter updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(TransporterFacade::delete($id), 'Transporter');
    }
}
