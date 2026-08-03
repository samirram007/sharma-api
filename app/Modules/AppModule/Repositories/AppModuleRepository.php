<?php

namespace Modules\AppModule\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AppModule\Contracts\AppModuleRepositoryInterface;
use Modules\AppModule\Models\AppModule;

class AppModuleRepository extends BaseRepository implements AppModuleRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        'icon',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(AppModule $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
