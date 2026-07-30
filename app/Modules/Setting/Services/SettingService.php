<?php

namespace Modules\Setting\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Setting\Contracts\SettingServiceInterface;
use Modules\Setting\Models\Setting;

class SettingService extends BaseService implements SettingServiceInterface
{
    protected string $modelClass = Setting::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Setting
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Setting
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Setting
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
