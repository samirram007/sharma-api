<?php

namespace Modules\VoucherType\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherType\Contracts\VoucherTypeRepositoryInterface;
use Modules\VoucherType\Models\VoucherType;

class VoucherTypeRepository extends BaseRepository implements VoucherTypeRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'print_name',
        'description',
        'icon',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'parent_id',
        // 'voucher_category_id',
        // 'voucher_classification_id',
        // 'is_financial',
        // 'is_effecting',
        // 'is_hidden',
        // 'is_system',
        'status',
    ];

    public function __construct(VoucherType $model)
    {
        parent::__construct($model);
    }
}
