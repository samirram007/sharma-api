<?php

namespace Modules\StockJournal\Services;

use App\Support\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\StockJournalEntry\Requests\StockJournalEntryRequest;

class StockJournalService extends BaseService implements StockJournalServiceInterface
{
    protected string $modelClass = StockJournal::class;

    protected array $defaultResource = [];

    protected $stockJournalEntryService;

    public function __construct(
        StockJournalEntryServiceInterface $stockJournalEntryService,
    ) {
        $this->stockJournalEntryService = $stockJournalEntryService;
    }

    public function store(array $data): StockJournal
    {

        if (empty($data['journal_no'])) {
            $data['journal_no'] = $this->generateJournalNo();
        }
        if (empty($data['journal_date'])) {

            $data['journal_date'] = Carbon::now();

        }
        if (empty($data['type'])) {
            $data['type'] = 'in';
        }

        $stockJournal = StockJournal::create($data);

        if (! empty($data['stock_journal_entries'])) {
            foreach ($data['stock_journal_entries'] as $key => $entryData) {
                $entryData['stock_journal_id'] = $stockJournal->id;
                $rules = (new StockJournalEntryRequest)->rules();
                $validatedStockJournalEntry = Validator::make($entryData, $rules)->validate();

                $data['stock_journal_entries'][$key] = $this->stockJournalEntryService->store($validatedStockJournalEntry);
            }

        }

        return $stockJournal;
    }

    protected function generateJournalNo(): string
    {
        // Implement your logic to generate a unique journal number
        $latestJournal = StockJournal::orderBy('id', 'desc')->first();
        $nextNumber = $latestJournal ? intval(substr($latestJournal->journal_no, -5)) + 1 : 1;

        return 'JRN-'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function update(array $data, int $id): StockJournal
    {
        $record = StockJournal::findOrFail($id);
        $record->update($data);

        if (! empty($data['stock_journal_entries'])) {

            $this->checkDelete(
                $data['stock_journal_entries'],
                $record
            );

            $rules = (new StockJournalEntryRequest)->rules();

            foreach ($data['stock_journal_entries'] as $entryData) {
                // need to have stock_journal_id = $record->id;
                // becoz while updating stock journal entries
                // stock_journal_id is not sent from frontend

                $entryData['stock_journal_id'] = $record->id;

                $validatedEntry = Validator::make($entryData, $rules)->validate();

                if (! empty($entryData['id'])) {

                    $this->stockJournalEntryService->update($validatedEntry, $entryData['id']);

                } else {
                    //  dump($validatedEntry);
                    $stockJournalEntry = $this->stockJournalEntryService->store($validatedEntry);
                }
            }

        }

        return $record->fresh();

    }

    private function checkDelete($data, $record)
    {
        $existingEntries = $this->stockJournalEntryService->getByStockJournalId($record->id);

        // delete entries not present in the update data
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
                $this->stockJournalEntryService->delete($existingEntry->id);
            }
        }
    }
}
