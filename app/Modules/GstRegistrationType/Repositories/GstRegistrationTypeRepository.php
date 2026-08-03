<?php

namespace Modules\GstRegistrationType\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeRepositoryInterface;
use Modules\GstRegistrationType\Models\GstRegistrationType;

class GstRegistrationTypeRepository extends BaseRepository implements GstRegistrationTypeRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(GstRegistrationType $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
