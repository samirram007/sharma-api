<?php

namespace Modules\FiscalYear\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\FiscalYear\Contracts\FiscalYearRepositoryInterface;
use Modules\FiscalYear\Models\FiscalYear;

class FiscalYearRepository extends BaseRepository implements FiscalYearRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        // 'start_date',
        // 'end_date',
        // 'assessment_year',
        // 'closed_at',
        // 'closed_by',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
        // 'company_id',
    ];

    public function __construct(FiscalYear $model)
    {
        parent::__construct($model);
    }
}
