<?php

namespace Modules\VoucherClassification\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherClassification\Contracts\VoucherClassificationServiceInterface;
use Modules\VoucherClassification\Models\VoucherClassification;

class VoucherClassificationService extends BaseService implements VoucherClassificationServiceInterface
{
    protected string $modelClass = VoucherClassification::class;

    protected array $defaultResource = [
        'voucher_type',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherClassification
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherClassification
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherClassification
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
