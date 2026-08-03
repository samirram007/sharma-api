<?php

namespace Modules\Currency\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Currency\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Models\Currency;

class CurrencyRepository extends BaseRepository implements CurrencyRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'symbol',
        // 'country',
        // 'exchange_rate',
        // 'decimal_places',
        // 'format',
        // 'thousands_separator',
        // 'decimal_separator',
        // 'symbol_position',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
