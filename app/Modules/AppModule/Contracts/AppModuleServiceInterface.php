<?php

namespace Modules\AppModule\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\AppModule\Models\AppModule;

interface AppModuleServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?AppModule;

    public function store(array $data): AppModule;

    public function update(array $data, int $id): AppModule;

    public function delete(int $id): bool;
}
