<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Journal\Facades\JournalFacade;
use Modules\Journal\Models\Journal;

// ---------------------------------------------------------------------------
// JournalFacade → JournalServiceInterface → JournalService delegation tests
// ---------------------------------------------------------------------------

/**
 * Create a journal record bypassing the model's $fillable mismatch.
 *
 * The Journal model's $fillable = ['name'] but the migration has columns
 * (voucher_id, entry_index, account_ledger_id, ...) — no 'name' column.
 * We insert directly via DB to work around this.
 */
function createJournal(array $overrides = []): Journal
{
    $id = DB::table('journals')->insertGetId(array_merge([
        'voucher_id' => 1,
        'entry_index' => 1,
        'account_ledger_id' => 1,
        'debit_amount' => 100.00,
        'credit_amount' => null,
    ], $overrides));

    return Journal::find($id);
}

describe('JournalFacade CRUD', function () {
    it('returns all journals via facade', function () {
        createJournal(['voucher_id' => 1, 'entry_index' => 1]);
        createJournal(['voucher_id' => 2, 'entry_index' => 1]);

        $result = JournalFacade::getAll();

        expect($result)->toHaveCount(2);
    });

    it('returns a single journal by id via facade', function () {
        $journal = createJournal(['voucher_id' => 10, 'entry_index' => 3]);

        $found = JournalFacade::getById($journal->id);

        expect($found)
            ->toBeInstanceOf(Journal::class)
            ->and($found->id)->toBe($journal->id);
    });

    it('deletes a journal via facade', function () {
        $journal = createJournal();

        $result = JournalFacade::delete($journal->id);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
    });

    it('deletes only the specified record', function () {
        $journalA = createJournal(['voucher_id' => 1]);
        $journalB = createJournal(['voucher_id' => 2]);

        JournalFacade::delete($journalA->id);

        $this->assertDatabaseMissing('journals', ['id' => $journalA->id]);
        $this->assertDatabaseHas('journals', ['id' => $journalB->id]);
    });
});

describe('JournalService edge cases', function () {
    it('throws ModelNotFoundException when getting non-existent journal', function () {
        expect(fn () => JournalFacade::getById(99999))
            ->toThrow(ModelNotFoundException::class);
    });

    it('throws ModelNotFoundException when deleting non-existent journal', function () {
        expect(fn () => JournalFacade::delete(99999))
            ->toThrow(ModelNotFoundException::class);
    });

    it('returns empty collection when no journals exist', function () {
        $result = JournalFacade::getAll();

        expect($result)->toHaveCount(0);
    });

    it('getAll returns correct number of multiple records', function () {
        createJournal(['voucher_id' => 1, 'entry_index' => 1]);
        createJournal(['voucher_id' => 1, 'entry_index' => 2]);
        createJournal(['voucher_id' => 2, 'entry_index' => 1]);

        $result = JournalFacade::getAll();

        expect($result)->toHaveCount(3);
    });

    it('getById returns correct record with expected data', function () {
        $journalA = createJournal(['voucher_id' => 1, 'entry_index' => 1, 'debit_amount' => 500]);
        createJournal(['voucher_id' => 1, 'entry_index' => 2, 'debit_amount' => 300]);

        $found = JournalFacade::getById($journalA->id);

        expect((float) $found->debit_amount)->toBe(500.00)
            ->and($found->entry_index)->toBe(1);
    });
});

// @todo: store() and update() cannot be tested through the facade until
// the Journal model's $fillable is fixed to match the migration columns
// (voucher_id, entry_index, account_ledger_id, debit_amount, credit_amount).
// Currently Journal::create() with any fillable data will fail.
