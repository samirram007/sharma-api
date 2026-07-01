<?php

namespace Modules\StockJournalEntryPurge\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalEntryPurge\Models\StockJournalEntryPurge;

interface StockJournalEntryPurgeServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?StockJournalEntryPurge;
    public function store(array $data): StockJournalEntryPurge;
    public function update(array $data, int $id): StockJournalEntryPurge;
    public function delete(int $id): bool;
}
