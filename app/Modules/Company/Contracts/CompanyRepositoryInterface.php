<?php

namespace Modules\Company\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Company\Models\Company;

interface CompanyRepositoryInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?Company;
}
