<?php

namespace Modules\StockJournalEntry\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Contracts\StockJournalGodownEntryServiceInterface;
use Modules\StockJournalGodownEntry\Requests\StockJournalGodownEntryRequest;

class StockJournalEntryService extends BaseService implements StockJournalEntryServiceInterface
{
    protected string $modelClass = StockJournalEntry::class;

    protected array $defaultResource = ['rate_unit'];

    public function __construct(
        protected StockJournalGodownEntryServiceInterface $stockJournalGodownEntryService,
    ) {}

    public function store(array $data): StockJournalEntry
    {
        $stockJournalEntry = StockJournalEntry::create($data);
        if (! empty($data['stock_journal_godown_entries'])) {
            foreach ($data['stock_journal_godown_entries'] as $key => $entryData) {

                $entryData['stock_journal_entry_id'] = $stockJournalEntry->id;
                $rules = (new StockJournalGodownEntryRequest)->rules();
                $validatedStockJournalGodownEntry = Validator::make($entryData, $rules)->validate();
                // dump($validatedStockJournalGodownEntry);
                $data['stock_journal_godown_entries'][$key] = $this->stockJournalGodownEntryService->store($validatedStockJournalGodownEntry);
            }
        }

        return $stockJournalEntry;
    }

    public function update(array $data, int $id): StockJournalEntry
    {
        $record = StockJournalEntry::findOrFail($id);
        $record->update($data);

        if (! empty($data['stock_journal_godown_entries'])) {
            $this->checkDelete($data['stock_journal_godown_entries'], $record);

            $rules = (new StockJournalGodownEntryRequest)->rules();

            foreach ($data['stock_journal_godown_entries'] as $godownData) {

                // dump($godownData);
                // This is added because while updating stock journal entry godown entries
                // need to have stock_journal_entry_id
                $godownData['stock_journal_entry_id'] = $record->id;
                $validatedGodownEntry = Validator::make(
                    $godownData,
                    $rules
                )->validate();

                if (! empty($godownData['id'])) {

                    $this->stockJournalGodownEntryService->update(
                        $validatedGodownEntry,
                        $godownData['id']
                    );

                } else {
                    // dump($validatedGodownEntry);
                    $this->stockJournalGodownEntryService->store(
                        $validatedGodownEntry
                    );
                }
            }
        }

        return $record->fresh();
    }

    public function getByStockJournalId(int $stockJournalId): Collection
    {
        return StockJournalEntry::with($this->defaultResource)
            ->where('stock_journal_id', $stockJournalId)
            ->get();
    }

    private function checkDelete($data, $record)
    {
        $existingEntries = $this->stockJournalGodownEntryService->getByStockJournalEntryId($record->id);
        foreach ($existingEntries as $existingEntry) {

            $found = false;

            foreach ($data as $entries) {
                if (
                    isset($entries['id']) &&
                    $entries['id'] == $existingEntry->id
                ) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $this->stockJournalGodownEntryService->delete($existingEntry->id);
            }
        }
    }
}
