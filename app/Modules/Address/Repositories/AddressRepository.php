<?php

namespace Modules\Address\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Address\Contracts\AddressRepositoryInterface;
use Modules\Address\Models\Address;

class AddressRepository extends BaseRepository implements AddressRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'line1',
        // 'line2',
        // 'landmark',
        // 'city',
        // 'postal_code',
        // 'latitude',
        // 'longitude',
        // 'post_office',
        // 'district',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'state_id',
        // 'country_id',
        // 'address_type',
        // 'is_primary',
        // 'addressable_id',
        // 'addressable_type',
    ];

    public function __construct(Address $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
