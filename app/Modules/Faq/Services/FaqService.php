<?php

namespace Modules\Faq\Services;

use App\Support\Services\BaseService;
use Modules\Faq\Contracts\FaqServiceInterface;
use Modules\Faq\Facades\FaqRepositoryFacade;
use Modules\Faq\Models\Faq;

class FaqService extends BaseService implements FaqServiceInterface
{
    protected string $modelClass = Faq::class;

    protected string $repositoryFacadeClass = FaqRepositoryFacade::class;

    public function __construct() {}
}
