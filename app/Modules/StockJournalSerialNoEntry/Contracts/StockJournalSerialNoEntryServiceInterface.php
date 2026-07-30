<?php

namespace Modules\StockJournalSerialNoEntry\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalSerialNoEntry\Models\StockJournalSerialNoEntry;

interface StockJournalSerialNoEntryServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?StockJournalSerialNoEntry;

    public function store(array $data): StockJournalSerialNoEntry;

    public function update(array $data, int $id): StockJournalSerialNoEntry;

    public function delete(int $id): bool;
}
