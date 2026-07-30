<?php

namespace Modules\Company\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Company\Models\Company;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function getAll(): Collection
    {
        return Company::all();
    }

    public function getById(int $id): ?Company
    {
        return Company::findOrFail($id);
    }
}
