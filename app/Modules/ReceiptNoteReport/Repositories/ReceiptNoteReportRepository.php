<?php

namespace Modules\ReceiptNoteReport\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\ReceiptNoteReport\Contracts\ReceiptNoteReportRepositoryInterface;
use Modules\Voucher\Models\Voucher;

class ReceiptNoteReportRepository extends BaseRepository implements ReceiptNoteReportRepositoryInterface
{
    protected array $searchableFields = [];

    protected array $filterableFields = [];

    public function __construct(Voucher $model)
    {
        parent::__construct($model);
    }
}
