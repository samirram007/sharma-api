<?php

namespace Modules\OrderStockJournal\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\OrderStockJournal\Models\OrderStockJournal;

interface OrderStockJournalServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?OrderStockJournal;

    public function store(array $data): OrderStockJournal;

    public function update(array $data, int $id): OrderStockJournal;

    public function delete(int $id): bool;
}
