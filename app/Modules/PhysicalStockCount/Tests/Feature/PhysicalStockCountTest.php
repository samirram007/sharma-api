<?php

use App\Enums\MovementType;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\PhysicalStockCount\Services\PhysicalStockCountService;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournal\Services\StockJournalService;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalEntry\Services\StockJournalEntryService;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockJournalGodownEntry\Services\StockJournalGodownEntryService;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;

/**
 * Feature tests for the physical stock count workflow:
 * draft → verified → adjusted.
 *
 * These tests run against the real schema (RefreshDatabase) and drive the real
 * service chain (PhysicalStockCountService → StockJournalService →
 * StockJournalEntryService → StockJournalGodownEntryService), exactly like the
 * fiscal year close E2E tests.
 */
beforeEach(function () {
    // Real user so Auth::id() resolves for the counted_by column.
    $this->user = User::create([
        'name' => 'PSC Test User',
        'email' => 'psc-test@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    // SKADJ voucher type is required by generateAdjustment().
    $category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);
    $this->skadjType = VoucherType::create([
        'name' => 'Stock Adjustment',
        'code' => 'SKADJ',
        'voucher_category_id' => $category->id,
        'is_system' => true,
    ]);

    // Stock master.
    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->godown = Godown::create(['name' => 'Main Godown', 'code' => 'MAIN']);
    $this->item = StockItem::create([
        'name' => 'Item A',
        'code' => 'ITEMA',
        'stock_unit_id' => $this->unit->id,
        'standard_cost' => 25,
    ]);

    $this->fiscalYear = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
        'status' => 'active',
        'company_id' => 1,
    ]);

    // Real service chain (no mocking needed).
    $godownEntryService = new StockJournalGodownEntryService;
    $stockJournalEntryService = new StockJournalEntryService($godownEntryService);
    $this->service = new PhysicalStockCountService(
        new StockJournalService($stockJournalEntryService),
        $stockJournalEntryService,
    );
});

/**
 * A physical quantity of 0 is a valid count — the item is fully missing and
 * gets verified as a complete loss.
 */
test('verify accepts a zero-physical count as a complete loss', function () {
    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
        'remarks' => 'Annual physical count',
        'items' => [
            [
                'stock_item_id' => $this->item->id,
                'system_quantity' => 10,
                'physical_quantity' => 0,
                'rate' => 25,
                'entry_order' => 1,
            ],
        ],
    ]);

    $verified = $this->service->verify($count->id);

    expect($verified->status)->toBe('verified');
    expect($verified->items)->toHaveCount(1);
    expect((float) $verified->items->first()->physical_quantity)->toBe(0.0);
    expect((float) $verified->items->first()->difference)->toBe(10.0);
});

/**
 * The core scenario: system stock of 10, physical count of 0 → a full loss of
 * 10 must be booked as an OUT movement, with batch/serial carried through, and
 * a SKADJ voucher linked to the adjustment journal.
 */
test('generateAdjustment books a full-loss OUT entry for a zero-physical item', function () {
    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
        'remarks' => 'Annual physical count',
        'items' => [
            [
                'stock_item_id' => $this->item->id,
                'batch_no' => 'B-LOT-001',
                'serial_no' => 'SN-001',
                'system_quantity' => 10,
                'physical_quantity' => 0,
                'rate' => 25,
                'entry_order' => 1,
            ],
        ],
    ]);
    $this->service->verify($count->id);

    $result = $this->service->generateAdjustment($count->id);

    // Count sheet moves to adjusted.
    expect($result->status)->toBe('adjusted');

    // Adjustment journal exists.
    $journal = StockJournal::where('type', 'ADJUSTMENT')->first();
    expect($journal)->not->toBeNull();

    // SKADJ voucher linked to the journal, stock-effecting only.
    $voucher = Voucher::where('stock_journal_id', $journal->id)->first();
    expect($voucher)->not->toBeNull();
    expect($voucher->voucher_type_id)->toBe($this->skadjType->id);
    expect($voucher->effects_stock)->toBeTrue();
    expect($voucher->effects_account)->toBeFalse();
    expect($voucher->fiscal_year_id)->toBe($this->fiscalYear->id);

    // Single stock journal entry: OUT, qty 10 (the full difference).
    $entry = $journal->stock_journal_entries()->first();
    expect($entry)->not->toBeNull();
    expect($entry->movement_type)->toBe(MovementType::OUT);
    expect((float) $entry->actual_quantity)->toBe(10.0);
    expect($entry->stock_item_id)->toBe($this->item->id);

    // Godown entry: OUT, qty 10, batch/serial carried through.
    $godownEntry = $entry->stock_journal_godown_entries()->first();
    expect($godownEntry)->not->toBeNull();
    expect($godownEntry->movement_type)->toBe(MovementType::OUT);
    expect((float) $godownEntry->actual_quantity)->toBe(10.0);
    expect($godownEntry->godown_id)->toBe($this->godown->id);
    expect($godownEntry->batch_no)->toBe('B-LOT-001');
    expect($godownEntry->serial_no)->toBe('SN-001');
    expect((float) $godownEntry->amount)->toBe(250.0);
    expect($godownEntry->remarks)->toContain('Stock loss');
    expect($godownEntry->remarks)->toContain('book: 10');
    expect($godownEntry->remarks)->toContain('physical: 0');

    // Nothing else was created (one journal, one entry, one godown entry).
    expect(StockJournal::count())->toBe(1);
    expect(StockJournalEntry::count())->toBe(1);
    expect(StockJournalGodownEntry::count())->toBe(1);
    expect(Voucher::count())->toBe(1);
});

/**
 * generateAdjustment() is guarded: the sheet must be verified first, and a
 * sheet without any variance is a no-op.
 */
test('generateAdjustment throws when the count sheet is not verified', function () {
    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
        'items' => [
            [
                'stock_item_id' => $this->item->id,
                'system_quantity' => 10,
                'physical_quantity' => 0,
                'rate' => 25,
                'entry_order' => 1,
            ],
        ],
    ]);

    // Even with a difference present, an un-verified sheet is rejected first.
    $this->service->generateAdjustment($count->id);
})->throws(Exception::class, 'must be verified');

/**
 * Populating a draft sheet reads the system quantities for the count godown
 * and fiscal year from the stock journals, grouping by item + batch/serial and
 * carrying the latest rate forward.
 */
test('populateSystemQuantities reads stock from journals into count rows', function () {
    // A source purchase voucher (its voucher type is irrelevant — populate only
    // joins on fiscal_year_id and the journal link).
    $purchaseType = VoucherType::create([
        'name' => 'Purchase',
        'code' => 'PURCH',
        'voucher_category_id' => $this->skadjType->voucher_category_id,
    ]);

    // 10 KG @ 25 in, then 3 KG out, for the same item/batch at godown MAIN.
    $inJournal = StockJournal::create([
        'journal_no' => 'SRC-0001',
        'journal_date' => '2026-01-15',
        'type' => 'in',
        'remarks' => 'Source purchase',
    ]);
    $inEntry = StockJournalEntry::create([
        'stock_journal_id' => $inJournal->id,
        'entry_order' => 1,
        'stock_item_id' => $this->item->id,
        'actual_quantity' => 10,
        'billing_quantity' => 10,
        'rate' => 25,
        'amount' => 250,
        'movement_type' => 'in',
    ]);
    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $inEntry->id,
        'entry_order' => 1,
        'godown_id' => $this->godown->id,
        'batch_no' => 'B-LOT-001',
        'serial_no' => 'SN-001',
        'actual_quantity' => 10,
        'billing_quantity' => 10,
        'rate' => 25,
        'amount' => 250,
        'movement_type' => 'in',
    ]);
    Voucher::create([
        'voucher_no' => 'SRC-0001',
        'voucher_date' => '2026-01-15',
        'voucher_type_id' => $purchaseType->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'stock_journal_id' => $inJournal->id,
        'status' => 'active',
    ]);

    $outJournal = StockJournal::create([
        'journal_no' => 'SRC-0002',
        'journal_date' => '2026-02-10',
        'type' => 'out',
        'remarks' => 'Dispatch',
    ]);
    $outEntry = StockJournalEntry::create([
        'stock_journal_id' => $outJournal->id,
        'entry_order' => 1,
        'stock_item_id' => $this->item->id,
        'actual_quantity' => 3,
        'billing_quantity' => 3,
        'rate' => 25,
        'amount' => 75,
        'movement_type' => 'out',
    ]);
    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $outEntry->id,
        'entry_order' => 1,
        'godown_id' => $this->godown->id,
        'batch_no' => 'B-LOT-001',
        'serial_no' => 'SN-001',
        'actual_quantity' => 3,
        'billing_quantity' => 3,
        'rate' => 25,
        'amount' => 75,
        'movement_type' => 'out',
    ]);
    Voucher::create([
        'voucher_no' => 'SRC-0002',
        'voucher_date' => '2026-02-10',
        'voucher_type_id' => $purchaseType->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'stock_journal_id' => $outJournal->id,
        'status' => 'active',
    ]);

    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
    ]);

    $populated = $this->service->populateSystemQuantities($count->id);

    // The two movements net down to a single row: 10 in − 3 out = 7.
    expect($populated->items)->toHaveCount(1);
    $item = $populated->items->first();
    expect((float) $item->system_quantity)->toBe(7.0);
    expect((float) $item->physical_quantity)->toBe(0.0);
    expect((float) $item->rate)->toBe(25.0);
    expect($item->stock_item_id)->toBe($this->item->id);
    expect($item->batch_no)->toBe('B-LOT-001');
    expect($item->serial_no)->toBe('SN-001');
});

/**
 * The surplus path: physical count above the system stock (physical 15 vs
 * system 10) must be booked as an IN movement of the surplus quantity (5).
 */
test('generateAdjustment books a surplus IN entry when physical exceeds system', function () {
    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
        'items' => [
            [
                'stock_item_id' => $this->item->id,
                'system_quantity' => 10,
                'physical_quantity' => 15,
                'rate' => 25,
                'entry_order' => 1,
            ],
        ],
    ]);
    $this->service->verify($count->id);

    $result = $this->service->generateAdjustment($count->id);

    expect($result->status)->toBe('adjusted');

    $journal = StockJournal::where('type', 'ADJUSTMENT')->first();
    $entry = $journal->stock_journal_entries()->first();
    expect($entry->movement_type)->toBe(MovementType::IN);
    expect((float) $entry->actual_quantity)->toBe(5.0);

    $godownEntry = $entry->stock_journal_godown_entries()->first();
    expect($godownEntry->movement_type)->toBe(MovementType::IN);
    expect((float) $godownEntry->actual_quantity)->toBe(5.0);
    expect((float) $godownEntry->amount)->toBe(125.0);
    expect($godownEntry->remarks)->toContain('Stock surplus');
});

/**
 * A mixed sheet with one loss and one surplus line: the adjustment journal and
 * voucher narrations must summarise the count sheet (loss/surplus lines + net
 * diff), each line keeps its batch/serial, and the movement mapping is
 * loss → OUT / surplus → IN.
 */
test('generateAdjustment writes descriptive remarks, carries batch/serial, and maps loss to OUT and surplus to IN', function () {
    $itemB = StockItem::create([
        'name' => 'Item B',
        'code' => 'ITEMB',
        'stock_unit_id' => $this->unit->id,
        'standard_cost' => 12,
    ]);

    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
        'remarks' => 'Annual physical count',
        'items' => [
            [
                'stock_item_id' => $this->item->id,
                'batch_no' => 'B-LOT-001',
                'serial_no' => 'SN-001',
                'system_quantity' => 10,
                'physical_quantity' => 0,
                'rate' => 25,
                'entry_order' => 1,
            ],
            [
                'stock_item_id' => $itemB->id,
                'batch_no' => 'B-LOT-002',
                'serial_no' => 'SN-002',
                'system_quantity' => 5,
                'physical_quantity' => 8,
                'rate' => 12,
                'entry_order' => 2,
            ],
        ],
    ]);
    $this->service->verify($count->id);

    $result = $this->service->generateAdjustment($count->id);

    expect($result->status)->toBe('adjusted');

    $journal = StockJournal::where('type', 'ADJUSTMENT')->first();
    $voucher = Voucher::where('stock_journal_id', $journal->id)->first();

    // Both the journal and the voucher narrate the count sheet summary:
    // 1 loss line (10 − 0), 1 surplus line (5 − 8), net diff 10 + (−3) = 7.
    $summary = "Stock adjustment from physical count #{$count->id} at Main Godown — 1 loss line(s), 1 surplus line(s), net diff 7";
    expect($journal->remarks)->toBe($summary);
    expect($voucher->remarks)->toBe($summary);

    // Two entries, ordered by entry_order: the loss first, the surplus second.
    $entries = $journal->stock_journal_entries()->orderBy('entry_order')->get();
    expect($entries)->toHaveCount(2);

    // Loss line → OUT, batch/serial carried, described as a stock loss.
    $lossEntry = $entries->firstWhere('stock_item_id', $this->item->id);
    expect($lossEntry->movement_type)->toBe(MovementType::OUT);
    expect((float) $lossEntry->actual_quantity)->toBe(10.0);

    $lossGodown = $lossEntry->stock_journal_godown_entries()->first();
    expect($lossGodown->movement_type)->toBe(MovementType::OUT);
    expect($lossGodown->batch_no)->toBe('B-LOT-001');
    expect($lossGodown->serial_no)->toBe('SN-001');
    expect($lossGodown->remarks)->toContain('Stock loss - Item A (batch: B-LOT-001)');
    expect($lossGodown->remarks)->toContain('book: 10');
    expect($lossGodown->remarks)->toContain('physical: 0');

    // Surplus line → IN, batch/serial carried, described as a stock surplus.
    $surplusEntry = $entries->firstWhere('stock_item_id', $itemB->id);
    expect($surplusEntry->movement_type)->toBe(MovementType::IN);
    expect((float) $surplusEntry->actual_quantity)->toBe(3.0);

    $surplusGodown = $surplusEntry->stock_journal_godown_entries()->first();
    expect($surplusGodown->movement_type)->toBe(MovementType::IN);
    expect($surplusGodown->batch_no)->toBe('B-LOT-002');
    expect($surplusGodown->serial_no)->toBe('SN-002');
    expect($surplusGodown->remarks)->toContain('Stock surplus - Item B (batch: B-LOT-002)');
    expect($surplusGodown->remarks)->toContain('book: 5');
    expect($surplusGodown->remarks)->toContain('physical: 8');

    // Net effect: two entries, two godown entries, one journal, one voucher.
    expect(StockJournal::count())->toBe(1);
    expect(StockJournalEntry::count())->toBe(2);
    expect(StockJournalGodownEntry::count())->toBe(2);
    expect(Voucher::count())->toBe(1);
});

/**
 * When a count line has no rate, the adjustment falls back to the item's
 * standard cost for valuation.
 */
test('generateAdjustment falls back to the item standard cost when rate is zero', function () {
    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
        'items' => [
            [
                'stock_item_id' => $this->item->id,
                'system_quantity' => 10,
                'physical_quantity' => 0,
                'rate' => 0,
                'entry_order' => 1,
            ],
        ],
    ]);
    $this->service->verify($count->id);

    $this->service->generateAdjustment($count->id);

    $journal = StockJournal::where('type', 'ADJUSTMENT')->first();
    $entry = $journal->stock_journal_entries()->first();
    $godownEntry = $entry->stock_journal_godown_entries()->first();

    // Item A was seeded with standard_cost = 25.
    expect((float) $godownEntry->rate)->toBe(25.0);
    expect((float) $godownEntry->amount)->toBe(250.0);
});

test('generateAdjustment throws when there are no variances', function () {
    $count = $this->service->store([
        'fiscal_year_id' => $this->fiscalYear->id,
        'godown_id' => $this->godown->id,
        'count_date' => '2026-02-28',
        'items' => [
            [
                'stock_item_id' => $this->item->id,
                'system_quantity' => 10,
                'physical_quantity' => 10,
                'rate' => 25,
                'entry_order' => 1,
            ],
        ],
    ]);
    $this->service->verify($count->id);

    $this->service->generateAdjustment($count->id);
})->throws(Exception::class, 'No variances found');
