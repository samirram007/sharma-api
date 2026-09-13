<?php

use App\Enums\MovementType;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Feature tests for GET /api/godown_item_batches/{item}/{godown} — the batch
 * picker endpoint behind the voucher BatchSelection combobox.
 *
 * Regression: PHP casts numeric-string array keys to int, so a batch_no like
 * "12345" used to come back as a JSON *number* and crash the frontend's
 * batchNo.trim(). The endpoint must always return batchNo as a string.
 */
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Batch Tester',
        'email' => 'batch-tester@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);

    $this->godown = Godown::create([
        'name' => 'Main Godown',
        'code' => 'MAIN',
        'status' => 'active',
        'storage_unit_type' => 'GODOWN',
    ]);

    $this->item = StockItem::create([
        'name' => 'Cement Bag',
        'code' => 'CEM',
        'status' => 'active',
    ]);
});

function createBatchEntry(
    int $itemId,
    int $godownId,
    ?string $batchNo,
    float $quantity = 10,
    string $movement = 'in',
): void {
    $journal = StockJournal::create([
        'journal_no' => 'SJ-'.uniqid(),
        'journal_date' => now(),
        'type' => 'stock',
    ]);

    $entry = StockJournalEntry::create([
        'stock_journal_id' => $journal->id,
        'entry_order' => 1,
        'stock_item_id' => $itemId,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'movement_type' => MovementType::from($movement),
    ]);

    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $entry->id,
        'entry_order' => 1,
        'godown_id' => $godownId,
        'batch_no' => $batchNo,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
    ]);
}

test('returns batchNo as a string even for numeric batch numbers', function () {
    // "915" is numeric: PHP's array-key int cast used to leak it as a number.
    createBatchEntry($this->item->id, $this->godown->id, '915');
    createBatchEntry($this->item->id, $this->godown->id, 'B-2026/07');

    $response = $this->withToken($this->token)
        ->getJson("/api/godown_item_batches/{$this->item->id}/{$this->godown->id}")
        ->assertOk();

    $batches = $response->json('data');
    expect($batches)->toHaveCount(2);

    foreach ($batches as $batch) {
        expect($batch['batchNo'])->toBeString();
    }

    expect(collect($batches)->pluck('batchNo')->sort()->values()->all())
        ->toBe(['915', 'B-2026/07']);
});

test('sums in and out movements per batch', function () {
    createBatchEntry($this->item->id, $this->godown->id, 'LOT-1', 10, 'in');
    createBatchEntry($this->item->id, $this->godown->id, 'LOT-1', 4, 'out');

    $response = $this->withToken($this->token)
        ->getJson("/api/godown_item_batches/{$this->item->id}/{$this->godown->id}")
        ->assertOk();

    $batch = collect($response->json('data'))->firstWhere('batchNo', 'LOT-1');
    expect((float) $batch['stockInHand'])->toBe(6.0);
});

test('a null batch_no is returned as an empty string', function () {
    createBatchEntry($this->item->id, $this->godown->id, null, 5, 'in');

    $response = $this->withToken($this->token)
        ->getJson("/api/godown_item_batches/{$this->item->id}/{$this->godown->id}")
        ->assertOk();

    expect($response->json('data.0.batchNo'))->toBe('');
});

test('an unknown godown yields an empty batch list', function () {
    $this->withToken($this->token)
        ->getJson("/api/godown_item_batches/{$this->item->id}/999999")
        ->assertOk()
        ->assertJsonPath('data', []);
});
