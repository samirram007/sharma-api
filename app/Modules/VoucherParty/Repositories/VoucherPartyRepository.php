<?php

namespace Modules\VoucherParty\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherParty\Contracts\VoucherPartyRepositoryInterface;
use Modules\VoucherParty\Models\VoucherParty;

class VoucherPartyRepository extends BaseRepository implements VoucherPartyRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        // 'mailing_name',
        // 'line1',
        // 'line2',
        // 'line3',
        'address',
        // 'gstin',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'voucher_id',
        // 'state_id',
        // 'country_id',
        // 'gst_registration_type_id',
        // 'place_of_supply_state_id',
    ];

    public function __construct(VoucherParty $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
