<?php

namespace Modules\Country\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Country\Contracts\CountryRepositoryInterface;
use Modules\Country\Contracts\CountryServiceInterface;
use Modules\Country\Models\Country;

class CountryService extends BaseService implements CountryServiceInterface
{
    protected string $modelClass = Country::class;

    protected array $defaultResource = ['states'];

    public function __construct(
        protected CountryRepositoryInterface $countryRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Country
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Country
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Country
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
