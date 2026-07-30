<?php

namespace Modules\Document\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Document\Contracts\DocumentServiceInterface;
use Modules\Document\Models\Document;

class DocumentService extends BaseService implements DocumentServiceInterface
{
    protected string $modelClass = Document::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Document
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Document
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Document
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
