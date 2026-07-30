<?php

namespace Modules\StockJournalStorageUnitEntry\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalStorageUnitEntry\Models\StockJournalStorageUnitEntry;

interface StockJournalStorageUnitEntryServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?StockJournalStorageUnitEntry;

    public function store(array $data): StockJournalStorageUnitEntry;

    public function update(array $data, int $id): StockJournalStorageUnitEntry;

    public function delete(int $id): bool;
}
