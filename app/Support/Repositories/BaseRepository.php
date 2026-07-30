<?php

namespace App\Support\Repositories;

use App\Support\Contracts\BaseRepositoryInterface;
use App\Support\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use Cacheable;

    protected Model $model;

    protected array $eagerLoad = [];

    /**
     * Base constructor to decide whether to use cache or not.
     */
    public function __construct(Model $model, bool $cacheable = true)
    {
        $this->model = $model;
        $this->useCache = $cacheable;
    }

    public function query()
    {
        return $this->model->newQuery();
    }

    public function cache(bool $enabled = true): static
    {
        $this->useCache = $enabled;

        return $this;
    }

    public function with(array $relations): static
    {
        $this->eagerLoad = $relations;

        return $this;
    }

    protected function getWith(array $with = []): array
    {
        $relations = array_merge($this->eagerLoad, $with);
        $this->eagerLoad = []; // Reset for next call

        return $relations;
    }

    public function all(array $with = [])
    {
        $with = $this->getWith($with);

        return $this->remember(
            $this->getCacheKey('all', $with),
            fn () => $this->query()->with($with)->get()
        );
    }

    public function find(int $id, array $with = [])
    {
        $with = $this->getWith($with);

        return $this->remember(
            $this->getCacheKey('find', [$id, $with]),
            fn () => $this->query()->with($with)->findOrFail($id)
        );
    }

    public function where(array $conditions, array $with = [])
    {
        $with = $this->getWith($with);

        return $this->remember(
            $this->getCacheKey('where', [$conditions, $with]),
            fn () => $this->query()->with($with)->where($conditions)->get()
        );
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        $with = $this->getWith($with);

        return $this->remember(
            $this->getCacheKey('paginate', [$perPage, request()->page ?? 1, $with]),
            fn () => $this->query()->with($with)->paginate($perPage)
        );
    }

    public function create(array $data)
    {
        $result = $this->model->create($data);
        $this->clearCache();

        return $result;
    }

    public function update(array $data, int $id)
    {
        $model = $this->find($id);
        $model->update($data);
        $this->clearCache();

        return $model;
    }

    public function delete($id)
    {
        $model = $this->find($id);
        $result = $model->delete();
        $this->clearCache();

        return $result;
    }
}
