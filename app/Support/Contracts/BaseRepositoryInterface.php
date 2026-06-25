<?php
namespace App\Support\Contracts;

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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query();

    /**
     * Set eager loading relations.
     *
     * @param array $relations
     * @return static
     */
    public function with(array $relations): static;

    /**
     * Enable or disable cache for the next query.
     *
     * @param bool $enabled
     * @return static
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
     * @param int $id
     * @return mixed
     */
    public function find(int $id, array $with = []);

    /**
     * Get records matching conditions.
     *
     * @param array $conditions
     * @return mixed
     */
    public function where(array $conditions, array $with = []);

    /**
     * Paginate records.
     *
     * @param int $perPage
     * @return mixed
     */
    public function paginate(int $perPage = 15, array $with = []);


    /**
     * Create a new record.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update a record by ID.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(array $data, int $id);

    /**
     * Delete a record by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id);
}
