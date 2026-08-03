<?php

namespace Modules\ManufacturingJournalReport\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\ManufacturingJournalReport\Contracts\ManufacturingJournalReportRepositoryInterface;
use Modules\Voucher\Models\Voucher;

class ManufacturingJournalReportRepository extends BaseRepository implements ManufacturingJournalReportRepositoryInterface
{
    protected array $searchableFields = [];

    protected array $filterableFields = [];

    public function __construct(Voucher $model)
    {
        parent::__construct($model);
    }
}
