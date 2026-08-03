<?php

namespace Modules\ConversionJournalReport\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\ConversionJournalReport\Contracts\ConversionJournalReportRepositoryInterface;
use Modules\Voucher\Models\Voucher;

class ConversionJournalReportRepository extends BaseRepository implements ConversionJournalReportRepositoryInterface
{
    protected array $searchableFields = [];

    protected array $filterableFields = [];

    public function __construct(Voucher $model)
    {
        parent::__construct($model);
    }
}
