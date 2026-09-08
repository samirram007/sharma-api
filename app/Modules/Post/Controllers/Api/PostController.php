<?php

namespace Modules\Post\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
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

    public function show(int $id): SuccessResource
    {
        $data = PostFacade::getById($id);

        return new PostResource($data, 'Post retrieved successfully');
    }

    public function store(PostRequest $request): SuccessResource
    {
        $data = PostFacade::store($request->validated());

        return new PostResource($data, 'Post created successfully');
    }

    public function update(PostRequest $request, int $id): SuccessResource
    {
        $data = PostFacade::update($request->validated(), $id);

        return new PostResource($data, 'Post updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(PostFacade::delete($id), 'Post');
    }
}
