<?php

namespace App\Support\Repositories;

use App\Support\Contracts\BaseRepositoryInterface;
use App\Support\Traits\Cacheable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use Cacheable;

    protected Model $model;

    protected array $eagerLoad = [];

    /**
     * Fields that can be searched via the search() method.
     * Override in child classes to enable searching.
     */
    protected array $searchableFields = [];

    /**
     * Fields that can be filtered via the filter() method.
     * Override in child classes to enable filtering.
     */
    protected array $filterableFields = [];

    /**
     * Current search query state (reset after terminal methods).
     */
    protected ?string $searchQuery = null;

    /**
     * Fields to search in for the current query.
     */
    protected array $searchInFields = [];

    /**
     * Current filter conditions (reset after terminal methods).
     */
    protected array $filterConditions = [];

    /**
     * Current sort orders (reset after terminal methods).
     */
    protected array $sortOrders = [];

    /**
     * Base constructor to decide whether to use cache or not.
     */
    public function __construct(Model $model, bool $cacheable = true)
    {
        $this->model = $model;
        $this->useCache = $cacheable;
    }

    public function query(): Builder
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

    public function all(array $with = []): Collection
    {
        $with = $this->getWith($with);
        $this->resetQueryState(); // Clear any lingering search/filter/sort state

        return $this->remember(
            $this->getCacheKey('all', $with),
            fn () => $this->query()->with($with)->get()
        );
    }

    public function find(int $id, array $with = [])
    {
        $with = $this->getWith($with);
        $this->resetQueryState();

        return $this->remember(
            $this->getCacheKey('find', [$id, $with]),
            fn () => $this->query()->with($with)->findOrFail($id)
        );
    }

    public function where(array $conditions, array $with = []): Collection
    {
        $with = $this->getWith($with);
        $this->resetQueryState();

        return $this->remember(
            $this->getCacheKey('where', [$conditions, $with]),
            fn () => $this->query()->with($with)->where($conditions)->get()
        );
    }

    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        $with = $this->getWith($with);
        $this->resetQueryState();

        return $this->remember(
            $this->getCacheKey('paginate', [$perPage, request()->page ?? 1, $with]),
            fn () => $this->query()->with($with)->paginate($perPage)
        );
    }

    // ──────────────────────────────────────────────
    //  Search, Filter, Sort (Server-Side)
    // ──────────────────────────────────────────────

    public function search(?string $search, array $fields = []): static
    {
        $this->searchQuery = $search;
        $this->searchInFields = $fields;

        return $this;
    }

    public function filter(array $filters): static
    {
        $this->filterConditions = $filters;

        return $this;
    }

    public function sortBy(string $column, string $direction = 'asc'): static
    {
        $this->sortOrders[] = [$column, $direction];

        return $this;
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);

        return $this->remember(
            $this->getCacheKey('getPaginated', [$perPage, $page, $this->searchQuery, $this->filterConditions, $this->sortOrders]),
            function () use ($perPage) {
                $query = $this->buildFilteredQuery();
                $result = $query->paginate($perPage);
                $this->resetQueryState();

                return $result;
            }
        );
    }

    public function getAllFiltered(): Collection
    {
        return $this->remember(
            $this->getCacheKey('getAllFiltered', [$this->searchQuery, $this->filterConditions, $this->sortOrders]),
            function () {
                $query = $this->buildFilteredQuery();
                $result = $query->get();
                $this->resetQueryState();

                return $result;
            }
        );
    }

    /**
     * Build a query with search, filter, and sort applied.
     */
    protected function buildFilteredQuery(): Builder
    {
        $query = $this->query()->with($this->eagerLoad);

        // Apply search
        if ($this->searchQuery !== null && $this->searchQuery !== '') {
            $fields = ! empty($this->searchInFields) ? $this->searchInFields : $this->searchableFields;
            if (! empty($fields)) {
                $query->where(function (Builder $q) use ($fields) {
                    $first = true;
                    foreach ($fields as $field) {
                        if ($first) {
                            $q->where($field, 'like', "%{$this->searchQuery}%");
                            $first = false;
                        } else {
                            $q->orWhere($field, 'like', "%{$this->searchQuery}%");
                        }
                    }
                });
            }
        }

        // Apply filters
        foreach ($this->filterConditions as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Skip if field is not in filterable list
            if (! empty($this->filterableFields) && ! in_array($field, $this->filterableFields)) {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif (str_starts_with((string) $value, '%') || str_ends_with((string) $value, '%')) {
                // Support LIKE filters (e.g., name=%john%)
                $query->where($field, 'like', $value);
            } else {
                $query->where($field, $value);
            }
        }

        // Apply sorting
        foreach ($this->sortOrders as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Reset all query state after a terminal method executes.
     */
    protected function resetQueryState(): void
    {
        $this->eagerLoad = [];
        $this->searchQuery = null;
        $this->searchInFields = [];
        $this->filterConditions = [];
        $this->sortOrders = [];
    }

    // ──────────────────────────────────────────────
    //  CRUD Operations
    // ──────────────────────────────────────────────

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

    public function delete($id): bool
    {
        $model = $this->find($id);
        $result = $model->delete();
        $this->clearCache();

        return $result;
    }
}
