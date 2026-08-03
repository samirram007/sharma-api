<?php

namespace Modules\Setting\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Setting\Contracts\SettingRepositoryInterface;
use Modules\Setting\Models\Setting;

class SettingRepository extends BaseRepository implements SettingRepositoryInterface
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

    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
