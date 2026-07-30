<?php

namespace Modules\StockCategory\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockCategory\Contracts\StockCategoryRepositoryInterface;
use Modules\StockCategory\Contracts\StockCategoryServiceInterface;
use Modules\StockCategory\Models\StockCategory;

class StockCategoryService extends BaseService implements StockCategoryServiceInterface
{
    protected string $modelClass = StockCategory::class;

    protected array $defaultResource = [
        'parent',
    ];

    public function __construct(
        protected StockCategoryRepositoryInterface $stockCategoryRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockCategory
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockCategory
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockCategory
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
