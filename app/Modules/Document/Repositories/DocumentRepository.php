<?php

namespace Modules\Document\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Document\Contracts\DocumentRepositoryInterface;
use Modules\Document\Models\Document;

class DocumentRepository extends BaseRepository implements DocumentRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(Document $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
