<?php

namespace Modules\VoucherType\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherType\Contracts\VoucherTypeRepositoryInterface;
use Modules\VoucherType\Contracts\VoucherTypeServiceInterface;
use Modules\VoucherType\Models\VoucherType;

class VoucherTypeService extends BaseService implements VoucherTypeServiceInterface
{
    protected string $modelClass = VoucherType::class;

    protected array $defaultResource = [
        'voucher_category',
    ];

    public function __construct(
        protected VoucherTypeRepositoryInterface $voucherTypeRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherType
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherType
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherType
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
