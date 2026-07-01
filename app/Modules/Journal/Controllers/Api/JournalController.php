<?php

namespace Modules\Journal\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Journal\Contracts\JournalServiceInterface;
use Modules\Journal\Resources\JournalResource;
use Modules\Journal\Resources\JournalCollection;
use Modules\Journal\Requests\JournalRequest;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

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
       $result = $this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result ? 'Journal deleted successfully' : 'Journal not found',
        ]);
    }
}
