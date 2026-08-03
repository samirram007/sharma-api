<?php

namespace Modules\Module\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Module\Contracts\ModuleRepositoryInterface;
use Modules\Module\Models\Module;

class ModuleRepository extends BaseRepository implements ModuleRepositoryInterface
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
    ];

    public function __construct(Module $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
