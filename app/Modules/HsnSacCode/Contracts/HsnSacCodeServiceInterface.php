<?php

namespace Modules\HsnSacCode\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\HsnSacCode\Models\HsnSacCode;

interface HsnSacCodeServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?HsnSacCode;

    public function store(array $data): HsnSacCode;

    public function update(array $data, int $id): HsnSacCode;

    public function delete(int $id): bool;
}
