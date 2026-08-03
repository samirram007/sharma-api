<?php

namespace Modules\Language\Services;

use App\Support\Services\BaseService;
use Modules\Language\Contracts\LanguageServiceInterface;
use Modules\Language\Facades\LanguageRepositoryFacade;
use Modules\Language\Models\Language;

class LanguageService extends BaseService implements LanguageServiceInterface
{
    protected string $modelClass = Language::class;

    protected string $repositoryFacadeClass = LanguageRepositoryFacade::class;

    public function __construct() {}
}
