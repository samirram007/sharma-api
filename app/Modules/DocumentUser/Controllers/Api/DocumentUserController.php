<?php

namespace Modules\DocumentUser\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\DocumentUser\Contracts\DocumentUserServiceInterface;
use Modules\DocumentUser\Requests\DocumentUserRequest;
use Modules\DocumentUser\Resources\DocumentUserCollection;
use Modules\DocumentUser\Resources\DocumentUserResource;

class DocumentUserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DocumentUserServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new DocumentUserCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new DocumentUserResource($data);
    }

    public function store(DocumentUserRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new DocumentUserResource($data, $messages = 'DocumentUser created successfully');
    }

    public function update(DocumentUserRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new DocumentUserResource($data, $messages = 'DocumentUser updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'DocumentUser');
    }
}
