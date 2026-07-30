<?php

namespace Modules\Language\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Language\Contracts\LanguageServiceInterface;
use Modules\Language\Models\Language;

class LanguageService extends BaseService implements LanguageServiceInterface
{
    protected string $modelClass = Language::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Language
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Language
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Language
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
