<?php

namespace Modules\Agent\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Agent\Contracts\AgentServiceInterface;
use Modules\Agent\Requests\AgentRequest;
use Modules\Agent\Resources\AgentCollection;
use Modules\Agent\Resources\AgentResource;

class AgentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AgentServiceInterface $service) {}

    public function index(): SuccessCollection
    {
        $data = $this->service->getAll();

        return new AgentCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->service->getById($id);

        return new AgentResource($data);
    }

    public function store(AgentRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());

        return new AgentResource($data, $messages = 'Agent created successfully');
    }

    public function update(AgentRequest $request, int $id): SuccessResource
    {
        $data = $this->service->update($request->validated(), $id);

        return new AgentResource($data, $messages = 'Agent updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Agent');
    }
}
