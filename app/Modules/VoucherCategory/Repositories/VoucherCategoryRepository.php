<?php

namespace Modules\VoucherCategory\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherCategory\Contracts\VoucherCategoryRepositoryInterface;
use Modules\VoucherCategory\Models\VoucherCategory;

class VoucherCategoryRepository extends BaseRepository implements VoucherCategoryRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        // 'module_link',
        'icon',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(VoucherCategory $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
