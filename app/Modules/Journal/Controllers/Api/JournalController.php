<?php

namespace Modules\Journal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Journal\Contracts\JournalServiceInterface;
use Modules\Journal\Requests\JournalRequest;
use Modules\Journal\Resources\JournalCollection;
use Modules\Journal\Resources\JournalResource;

class JournalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected JournalServiceInterface $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();

        return (new JournalCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getById($id);

        return $this->resourceResponse(
            new JournalResource($data),
            'Journal retrieved successfully'
        );
    }

    public function store(JournalRequest $request): JsonResponse
    {
        $data = $this->service->store($request->validated());

        return $this->resourceResponse(
            new JournalResource($data),
            'Journal created successfully',
            201
        );
    }

    public function update(JournalRequest $request, int $id): JsonResponse
    {
        $data = $this->service->update($request->validated(), $id);

        return $this->resourceResponse(
            new JournalResource($data),
            'Journal updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Journal');
    }
}
