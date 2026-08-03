<?php

namespace App\Support\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface BaseRepositoryInterface
{
    /**
     * Disable cache for the next query.
     */
    public function withoutCache(): static;

    /**
     * Clear the cache for this repository.
     */
    public function clearCache(): void;

    /**
     * Get a new query builder instance.
     *
     * @return Builder
     */
    public function query();

    /**
     * Set eager loading relations.
     */
    public function with(array $relations): static;

    /**
     * Enable or disable cache for the next query.
     */
    public function cache(bool $enabled = true): static;

    /**
     * Get all records.
     */
    public function all(array $with = []): Collection;

    /**
     * Find a record by ID.
     *
     * @return mixed
     */
    public function find(int $id, array $with = []);

    /**
     * Get records matching conditions.
     */
    public function where(array $conditions, array $with = []): Collection;

    /**
     * Paginate records (basic).
     */
    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator;

    /**
     * Set search term for the next query.
     * Searches across searchable fields, or the given fields.
     */
    public function search(?string $search, array $fields = []): static;

    /**
     * Set filter conditions for the next query.
     * Only applies filters for fields defined in filterableFields.
     */
    public function filter(array $filters): static;

    /**
     * Add sort order for the next query.
     */
    public function sortBy(string $column, string $direction = 'asc'): static;

    /**
     * Get paginated results with search, filter, and sort applied.
     * Resets query state after execution.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all results with search and filter applied (no pagination).
     * Resets query state after execution.
     */
    public function getAllFiltered(): Collection;

    /**
     * Create a new record.
     *
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update a record by ID.
     *
     * @return mixed
     */
    public function update(array $data, int $id);

    /**
     * Delete a record by ID.
     */
    public function delete(int $id): bool;
}
