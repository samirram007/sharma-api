<?php
namespace App\Modules\Company\Contracts;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Collection;

interface CompanyRepositoryInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?Company;
}
