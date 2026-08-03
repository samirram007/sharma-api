<?php

namespace Modules\Language\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Language\Contracts\LanguageRepositoryInterface;
use Modules\Language\Models\Language;

class LanguageRepository extends BaseRepository implements LanguageRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
    ];

    public function __construct(Language $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
