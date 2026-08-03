<?php

namespace Modules\Company\Repositories;

use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Company\Contracts\CompanyRepositoryInterface;
use Modules\Company\Models\Company;

class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    protected array $searchableFields = [
        'name',
        'code',
        'email',
        'phone',
    ];

    protected array $filterableFields = [
        'status',
    ];

    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function getAll(): Collection
    {
        return Company::all();
    }

    public function getById(int $id): ?Company
    {
        return Company::findOrFail($id);
    }
}
