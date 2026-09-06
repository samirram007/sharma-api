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
        'title', 'content', 'link',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'document_type_id',
        'document_status_id',
        'user_id',
        'company_id',
    ];

    public function __construct(Document $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
