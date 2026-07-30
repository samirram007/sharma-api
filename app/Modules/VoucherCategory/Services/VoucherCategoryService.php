<?php

namespace Modules\VoucherCategory\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherCategory\Contracts\VoucherCategoryServiceInterface;
use Modules\VoucherCategory\Models\VoucherCategory;

class VoucherCategoryService extends BaseService implements VoucherCategoryServiceInterface
{
    protected string $modelClass = VoucherCategory::class;

    protected array $defaultResource = [
        'voucher_types',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherCategory
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherCategory
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherCategory
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
