<?php

namespace App\Support\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseServiceInterface
{
    /**
     * Get all records — returns paginated results when ?per_page or ?search
     * is present in the request, otherwise returns a simple collection.
     */
    public function getAll(): Collection|LengthAwarePaginator;

    /**
     * Find a record by ID with default eager loading.
     */
    public function getById(int $id): ?Model;

    /**
     * Create a new record.
     */
    public function store(array $data): Model;

    /**
     * Update a record by ID and return the fresh instance.
     */
    public function update(array $data, int $id): Model;

    /**
     * Delete a record by ID.
     */
    public function delete(int $id): bool;
}
