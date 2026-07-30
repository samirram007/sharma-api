<?php

namespace App\Support\Contracts;

use Illuminate\Database\Eloquent\Builder;

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
     *
     * @return mixed
     */
    public function all(array $with = []);

    /**
     * Find a record by ID.
     *
     * @return mixed
     */
    public function find(int $id, array $with = []);

    /**
     * Get records matching conditions.
     *
     * @return mixed
     */
    public function where(array $conditions, array $with = []);

    /**
     * Paginate records.
     *
     * @return mixed
     */
    public function paginate(int $perPage = 15, array $with = []);

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
     *
     * @return bool
     */
    public function delete(int $id);
}
