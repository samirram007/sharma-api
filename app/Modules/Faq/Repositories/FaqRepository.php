<?php

namespace Modules\Faq\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Faq\Contracts\FaqRepositoryInterface;
use Modules\Faq\Models\Faq;

class FaqRepository extends BaseRepository implements FaqRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'question', 'answer', 'category',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'category', 'is_published',
    ];

    public function __construct(Faq $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
