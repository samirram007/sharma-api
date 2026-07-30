<?php

namespace Modules\Post\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Post\Contracts\PostServiceInterface;
use Modules\Post\Models\Post;

class PostService extends BaseService implements PostServiceInterface
{
    protected string $modelClass = Post::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Post
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Post
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Post
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
