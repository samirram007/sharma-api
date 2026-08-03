<?php

namespace Modules\Post\Services;

use App\Support\Services\BaseService;
use Modules\Post\Contracts\PostServiceInterface;
use Modules\Post\Facades\PostRepositoryFacade;
use Modules\Post\Models\Post;

class PostService extends BaseService implements PostServiceInterface
{
    protected string $modelClass = Post::class;

    protected string $repositoryFacadeClass = PostRepositoryFacade::class;

    public function __construct() {}
}
