<?php

namespace Modules\FiscalYear\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\FiscalYear\Contracts\FiscalYearRepositoryInterface;
use Modules\FiscalYear\Contracts\FiscalYearServiceInterface;
use Modules\FiscalYear\Models\FiscalYear;

class FiscalYearService extends BaseService implements FiscalYearServiceInterface
{
    protected string $modelClass = FiscalYear::class;

    protected array $defaultResource = [
        'company',
    ];

    public function __construct(
        protected FiscalYearRepositoryInterface $fiscalYearRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?FiscalYear
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): FiscalYear
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): FiscalYear
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
