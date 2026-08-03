<?php

namespace Modules\Voucher\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Voucher\Contracts\VoucherRepositoryInterface;
use Modules\Voucher\Models\Voucher;

class VoucherRepository extends BaseRepository implements VoucherRepositoryInterface
{
    protected array $searchableFields = [
        'voucher_no',
        'reference_no',
        'remarks',
        'status',
    ];

    protected array $filterableFields = [
        'voucher_type_id',
        'status',
        'fiscal_year_id',
        'company_id',
        'module',
        'is_effecting',
        'effects_account',
        'effects_stock',
    ];

    public function __construct(Voucher $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a voucher by ID with a shared lock (for update path transaction safety).
     * This prevents concurrent transactions from modifying the same voucher.
     */
    public function findWithLock(int $id): mixed
    {
        $with = $this->getWith([]);

        return $this->query()
            ->with($with)
            ->lockForUpdate()
            ->findOrFail($id);
    }
}
