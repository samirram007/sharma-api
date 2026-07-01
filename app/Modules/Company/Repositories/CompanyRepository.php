<?php
namespace Modules\Company\Repositories;

use Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Collection;

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
