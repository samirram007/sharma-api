<?php

namespace App\Support\Services;

use App\Support\Contracts\BaseRepositoryInterface;
use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService implements BaseServiceInterface
{
    /**
     * The model class to use for CRUD operations.
     * Must be set by child classes.
     *
     * @var class-string<Model>
     */
    protected string $modelClass;

    /**
     * Default relations to eager-load on queries.
     */
    protected array $defaultResource = [];

    /**
     * The RepositoryFacade class to use for data access.
     * Child services should set this to their module's RepositoryFacade:
     *   protected string $repositoryFacadeClass = CurrencyRepositoryFacade::class;
     *
     * When set, all CRUD operations resolve the repository through this facade.
     */
    protected string $repositoryFacadeClass = '';

    /**
     * Get the repository instance resolved from the RepositoryFacade.
     */
    protected function getRepository(): ?BaseRepositoryInterface
    {
        if ($this->repositoryFacadeClass) {
            $facade = $this->repositoryFacadeClass;

            // getFacadeRoot() is public and resolves the underlying service/repository
            // bound in the container. Do NOT call getFacadeAccessor() directly — it is
            // protected, so an external call triggers Facade::__callStatic and fails.
            return $facade::getFacadeRoot();
        }

        return $this->repository ?? null;
    }

    /**
     * Get a new query builder instance for the model.
     */
    protected function query(): Builder
    {
        return $this->modelClass::query();
    }

    /**
     * Get a new query builder instance with default eager loading applied.
     */
    protected function queryWithResource(): Builder
    {
        $query = $this->query();

        if (! empty($this->defaultResource)) {
            $query->with($this->defaultResource);
        }

        return $query;
    }

    // ──────────────────────────────────────────────
    //  Public API (implements BaseServiceInterface)
    // ──────────────────────────────────────────────

    /**
     * Get all records — automatically applies pagination and search
     * from request query parameters (?per_page, ?search).
     *
     * When request has ?per_page= or ?search=, returns paginated results.
     * Otherwise returns all records as a simple collection.
     */
    public function getAll(): Collection|LengthAwarePaginator
    {
        $perPage = request()->integer('per_page', 0);
        $search = request()->input('search', '');

        if ($perPage > 0 || $search !== '') {
            return $this->getAutoPaginated($perPage > 0 ? $perPage : 15, $search);
        }

        return $this->getAllRecords();
    }

    public function getById(int $id): ?Model
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Model
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Model
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }

    /**
     * Automatically apply search and pagination from request params.
     */
    protected function getAutoPaginated(int $perPage, string $search): LengthAwarePaginator
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo
                ->with($this->defaultResource)
                ->search($search ?: null, [])
                ->getPaginated($perPage);
        }

        return $this->queryWithResource()->paginate($perPage);
    }

    /**
     * Get paginated results — delegates to repository if available.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo
                ->with($this->defaultResource)
                ->getPaginated($perPage);
        }

        return $this->queryWithResource()->paginate($perPage);
    }

    /**
     * Get paginated results with server-side search.
     */
    public function searchAndPaginate(?string $search, int $perPage = 15, array $searchFields = []): LengthAwarePaginator
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo
                ->with($this->defaultResource)
                ->search($search, $searchFields)
                ->getPaginated($perPage);
        }

        $query = $this->queryWithResource();

        if ($search && ! empty($searchFields)) {
            $query->where(function (Builder $q) use ($search, $searchFields) {
                foreach ($searchFields as $i => $field) {
                    if ($i === 0) {
                        $q->where($field, 'like', "%{$search}%");
                    } else {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get filtered results with server-side search, filter, and sort.
     */
    public function getFiltered(
        ?string $search = null,
        array $filters = [],
        string $sortBy = 'id',
        string $sortDirection = 'asc',
        array $searchFields = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo
                ->with($this->defaultResource)
                ->search($search, $searchFields)
                ->filter($filters)
                ->sortBy($sortBy, $sortDirection)
                ->getPaginated($perPage);
        }

        $query = $this->queryWithResource();

        if ($search && ! empty($searchFields)) {
            $query->where(function (Builder $q) use ($search, $searchFields) {
                foreach ($searchFields as $i => $field) {
                    if ($i === 0) {
                        $q->where($field, 'like', "%{$search}%");
                    } else {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    // ──────────────────────────────────────────────
    //  Protected helpers
    // ──────────────────────────────────────────────

    /**
     * Get all records with default eager loading.
     */
    protected function getAllRecords(): Collection
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo->with($this->defaultResource)->all();
        }

        return $this->queryWithResource()->get();
    }

    /**
     * Find a record by ID with default eager loading, or fail.
     */
    protected function findOrFail(int $id): Model
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo->with($this->defaultResource)->find($id);
        }

        return $this->queryWithResource()->findOrFail($id);
    }

    /**
     * Create a new record.
     */
    protected function createRecord(array $data): Model
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo->create($data);
        }

        return $this->modelClass::create($data);
    }

    /**
     * Update a record by ID and return the fresh instance.
     */
    protected function updateRecord(int $id, array $data): Model
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo->update($data, $id);
        }

        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    /**
     * Delete a record by ID.
     */
    protected function deleteRecord(int $id): bool
    {
        $repo = $this->getRepository();

        if ($repo) {
            return $repo->delete($id);
        }

        $record = $this->modelClass::findOrFail($id);

        return $record->delete();
    }
}
