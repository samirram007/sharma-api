<?php

namespace Modules\Document\Services;

use App\Support\Services\BaseService;
use Modules\Document\Contracts\DocumentServiceInterface;
use Modules\Document\Facades\DocumentRepositoryFacade;
use Modules\Document\Models\Document;

class DocumentService extends BaseService implements DocumentServiceInterface
{
    protected string $modelClass = Document::class;

    protected string $repositoryFacadeClass = DocumentRepositoryFacade::class;

    public function __construct() {}
}
