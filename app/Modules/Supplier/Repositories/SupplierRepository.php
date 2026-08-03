<?php

namespace Modules\Supplier\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Supplier\Contracts\SupplierRepositoryInterface;
use Modules\Supplier\Models\Supplier;

class SupplierRepository extends BaseRepository implements SupplierRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        // 'gstin',
        // 'pan',
        // 'contact_person',
        // 'contact_no',
        'phone',
        'email',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'gst_registration_type_id',
        'status',
    ];

    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }
}
