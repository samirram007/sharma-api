<?php

namespace Modules\Post\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Post\Contracts\PostServiceInterface;
use Modules\Post\Requests\PostRequest;
use Modules\Post\Resources\PostCollection;
use Modules\Post\Resources\PostResource;

class PostController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PostServiceInterface $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->getAll();

        return (new PostCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getById($id);

        return $this->resourceResponse(
            new PostResource($data),
            'Post retrieved successfully'
        );
    }

    public function store(PostRequest $request): JsonResponse
    {
        $data = $this->service->store($request->validated());

        return $this->resourceResponse(
            new PostResource($data),
            'Post created successfully',
            201
        );
    }

    public function update(PostRequest $request, int $id): JsonResponse
    {
        $data = $this->service->update($request->validated(), $id);

        return $this->resourceResponse(
            new PostResource($data),
            'Post updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse($this->service->delete($id), 'Post');
    }
}
