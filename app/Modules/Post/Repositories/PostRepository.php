<?php

namespace Modules\Post\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Post\Contracts\PostRepositoryInterface;
use Modules\Post\Models\Post;

class PostRepository extends BaseRepository implements PostRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
    ];

    public function __construct(Post $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
