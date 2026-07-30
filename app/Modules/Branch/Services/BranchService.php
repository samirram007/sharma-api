<?php

namespace Modules\Branch\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Branch\Contracts\BranchRepositoryInterface;
use Modules\Branch\Contracts\BranchServiceInterface;
use Modules\Branch\Models\Branch;

class BranchService extends BaseService implements BranchServiceInterface
{
    public function __construct(
        protected BranchRepositoryInterface $branchRepository
    ) {}

    protected string $modelClass = Branch::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Branch
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Branch
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Branch
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
