<?php

namespace Modules\Post\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\Post\Facades\PostFacade;
use Modules\Post\Requests\PostRequest;
use Modules\Post\Resources\PostCollection;
use Modules\Post\Resources\PostResource;

class PostController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = PostFacade::getAll();

        return (new PostCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = PostFacade::getById($id);

        return $this->resourceResponse(
            new PostResource($data),
            'Post retrieved successfully'
        );
    }

    public function store(PostRequest $request): JsonResponse
    {
        $data = PostFacade::store($request->validated());

        return $this->resourceResponse(
            new PostResource($data),
            'Post created successfully',
            201
        );
    }

    public function update(PostRequest $request, int $id): JsonResponse
    {
        $data = PostFacade::update($request->validated(), $id);

        return $this->resourceResponse(
            new PostResource($data),
            'Post updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(PostFacade::delete($id), 'Post');
    }
}
