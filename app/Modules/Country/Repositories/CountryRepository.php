<?php

namespace Modules\Country\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Country\Contracts\CountryRepositoryInterface;
use Modules\Country\Models\Country;

class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        // 'phone_code',
        // 'iso_code',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
    ];

    public function __construct(Country $model)
    {
        parent::__construct($model);
    }
}
