<?php

namespace Modules\Currency\Services;

use Modules\Currency\Contracts\CurrencyServiceInterface;
use Modules\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

class CurrencyService implements CurrencyServiceInterface
{
    public function getAll(): Collection
    {
        return Currency::all();
    }

    public function getById(int $id): Currency
    {
        return Currency::findOrFail($id);
    }

    public function store(array $data): Currency
    {
        return Currency::create($data);
    }

    public function update(array $data, int $id): Currency
    {
        $record = Currency::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = Currency::findOrFail($id);
        return $record->delete();
    }
}
