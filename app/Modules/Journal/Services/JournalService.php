<?php

namespace Modules\Journal\Services;

use App\Support\Services\BaseService;
use Modules\Journal\Contracts\JournalServiceInterface;
use Modules\Journal\Facades\JournalRepositoryFacade;
use Modules\Journal\Models\Journal;

class JournalService extends BaseService implements JournalServiceInterface
{
    protected string $modelClass = Journal::class;

    protected string $repositoryFacadeClass = JournalRepositoryFacade::class;

    public function __construct() {}
}
