<?php

namespace App\Support\Services;

use App\Support\Contracts\BaseServiceInterface;
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
    //  Delegates to protected helpers so child
    //  classes that override the public methods
    //  and call the protected helpers don't cause
    //  infinite recursion.
    // ──────────────────────────────────────────────

    public function getAll(): Collection
    {
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

    // ──────────────────────────────────────────────
    //  Protected helpers (original implementation)
    //  Retained so existing child services that call
    //  these directly (e.g. in overridden public
    //  methods) continue to work without recursion.
    // ──────────────────────────────────────────────

    /**
     * Get all records with default eager loading.
     */
    protected function getAllRecords(): Collection
    {
        return $this->queryWithResource()->get();
    }

    /**
     * Find a record by ID with default eager loading, or fail.
     */
    protected function findOrFail(int $id): Model
    {
        return $this->queryWithResource()->findOrFail($id);
    }

    /**
     * Create a new record.
     */
    protected function createRecord(array $data): Model
    {
        return $this->modelClass::create($data);
    }

    /**
     * Update a record by ID and return the fresh instance.
     */
    protected function updateRecord(int $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    /**
     * Delete a record by ID.
     */
    protected function deleteRecord(int $id): bool
    {
        $record = $this->modelClass::findOrFail($id);

        return $record->delete();
    }
}
