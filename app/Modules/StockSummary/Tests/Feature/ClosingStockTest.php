<?php

namespace Modules\StockSummary\Tests\Feature;

use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockSummary\Resources\ClosingStockResource;
use Modules\StockSummary\Services\StockSummaryService;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\UserFiscalYear\Services\UserFiscalYearService;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;

/**
 * Create a stock movement voucher (voucher → stock journal → entry → godown entry).
 */
function createStockMovement(
    string $voucherNo,
    string $voucherDate,
    StockItem $item,
    StockUnit $unit,
    Godown $godown,
    int $fiscalYearId,
    float $quantity,
    float $rate,
    VoucherType $voucherType,
    string $movementType = 'in',
    ?string $batchNo = null,
): Voucher {
    $amount = round($quantity * $rate, 2);

    $voucher = Voucher::create([
        'voucher_no' => $voucherNo,
        'voucher_date' => $voucherDate,
        'voucher_type_id' => $voucherType->id,
        'fiscal_year_id' => $fiscalYearId,
        'company_id' => 1,
        'module' => 'sales',
        'status' => 'active',
    ]);

    $journal = StockJournal::create([
        'journal_no' => $voucherNo,
        'journal_date' => $voucherDate,
        'type' => $movementType === 'in' ? 'PURCHASE' : 'SALE',
        'remarks' => 'Test movement',
    ]);
    $voucher->update(['stock_journal_id' => $journal->id]);

    $entry = StockJournalEntry::create([
        'stock_journal_id' => $journal->id,
        'entry_order' => 1,
        'stock_item_id' => $item->id,
        'stock_unit_id' => $unit->id,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'rate' => $rate,
        'rate_unit_id' => $unit->id,
        'amount' => $amount,
        'movement_type' => $movementType,
    ]);

    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $entry->id,
        'entry_order' => 1,
        'godown_id' => $godown->id,
        'batch_no' => $batchNo,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'rate' => $rate,
        'amount' => $amount,
        'movement_type' => $movementType,
    ]);

    return $voucher;
}

/**
 * Create an opening-stock (9010 / OPNSK) voucher → stock journal → entry → godown entry.
 */
function createOpeningStockMovement(
    string $voucherNo,
    string $voucherDate,
    StockItem $item,
    StockUnit $unit,
    Godown $godown,
    int $fiscalYearId,
    float $quantity,
    float $rate,
    VoucherType $voucherType,
    string $movementType = 'in',
): Voucher {
    $amount = round($quantity * $rate, 2);

    $voucher = Voucher::create([
        'voucher_no' => $voucherNo,
        'voucher_date' => $voucherDate,
        'voucher_type_id' => $voucherType->id,
        'fiscal_year_id' => $fiscalYearId,
        'company_id' => 1,
        'module' => 'opening_stock',
        'status' => 'active',
    ]);

    $journal = StockJournal::create([
        'journal_no' => $voucherNo,
        'journal_date' => $voucherDate,
        'type' => 'OPNSK',
        'remarks' => 'Opening stock',
    ]);
    $voucher->update(['stock_journal_id' => $journal->id]);

    $entry = StockJournalEntry::create([
        'stock_journal_id' => $journal->id,
        'entry_order' => 1,
        'stock_item_id' => $item->id,
        'stock_unit_id' => $unit->id,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'rate' => $rate,
        'rate_unit_id' => $unit->id,
        'amount' => $amount,
        'movement_type' => $movementType,
    ]);

    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $entry->id,
        'entry_order' => 1,
        'godown_id' => $godown->id,
        'batch_no' => null,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'rate' => $rate,
        'amount' => $amount,
        'movement_type' => $movementType,
    ]);

    return $voucher;
}

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Closing Stock Test User',
        'email' => 'closing-stock-test@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    $this->fiscalYear = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
        'status' => 'active',
        'company_id' => 1,
    ]);

    // User's fiscal year mapping — reporting period defaults to the full FY.
    $this->userFiscalYear = UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
    ]);

    // Stock master
    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->mainGodown = Godown::create(['name' => 'Main Godown', 'code' => 'MAIN']);
    $this->storeGodown = Godown::create(['name' => 'Store', 'code' => 'STORE']);
    $this->item = StockItem::create(['name' => 'Item A', 'code' => 'ITEMA', 'stock_unit_id' => $this->unit->id]);
    $this->itemB = StockItem::create(['name' => 'Item B', 'code' => 'ITEMB', 'stock_unit_id' => $this->unit->id]);

    // Voucher types — SALE for the source movements, CLSSK for the frozen branch.
    $this->category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);
    $this->saleType = VoucherType::create([
        'name' => 'Sales',
        'code' => 'SALE',
        'voucher_category_id' => $this->category->id,
        'is_system' => true,
    ]);
    $this->clsskType = VoucherType::create([
        'name' => 'Closing Stock',
        'code' => 'CLSSK',
        'voucher_category_id' => $this->category->id,
        'is_system' => true,
    ]);
    $this->opnskType = VoucherType::create([
        'name' => 'Opening Stock',
        'code' => 'OPNSK',
        'voucher_category_id' => $this->category->id,
        'is_system' => true,
    ]);

    $this->service = new StockSummaryService(new UserFiscalYearService);
});

// ---------------------------------------------------------------------------
//  Running branch (no closing journal yet)
// ---------------------------------------------------------------------------

test('closingStock() computes the running closing stock with godown and batch detail when no closing journal exists', function () {
    // Item A: +100 @10 (batch B1, Main), +50 @12 (batch B2, Main), -30 (batch B1, Main)
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-08-10', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B2');
    createStockMovement('SALE-0003', '2025-09-05', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 30, 10, $this->saleType, 'out', 'B1');

    // Item B: +10 @5 (no batch, Store)
    createStockMovement('SALE-0004', '2025-10-01', $this->itemB, $this->unit, $this->storeGodown, $this->fiscalYear->id, 10, 5, $this->saleType);

    $result = $this->service->closingStock();

    expect($result['source'])->toBe('running');
    expect($result['as_of_date'])->toBe('2026-03-31');
    expect($result['closing_voucher_id'])->toBeNull();
    expect($result['total_items'])->toBe(2);
    expect($result['total_quantity'])->toBe(130.0);
    expect($result['total_amount'])->toBe(1330.0);

    // Item A — closing = 100 + 50 - 30 = 120
    $itemA = collect($result['items'])->firstWhere('item_id', $this->item->id);
    expect($itemA['item_name'])->toBe('Item A');
    expect($itemA['closing_quantity'])->toBe(120.0);
    // Weighted average rate of inward: (100*10 + 50*12) / 150 = 1600/150
    expect($itemA['rate'])->toBe(1600 / 150);
    expect($itemA['closing_amount'])->toBe(1280.0);

    $godown = $itemA['godown_details'][0];
    expect($godown['godown_id'])->toBe($this->mainGodown->id);
    expect($godown['godown_name'])->toBe('Main Godown');
    expect($godown['closing_quantity'])->toBe(120.0);

    // Batch-level detail within the godown
    $batches = collect($godown['batch_details']);
    expect($batches->firstWhere('batch_no', 'B1')['quantity'])->toBe(70.0);
    expect($batches->firstWhere('batch_no', 'B2')['quantity'])->toBe(50.0);

    // Item B — no batch number → bucket with a null batch
    $itemB = collect($result['items'])->firstWhere('item_id', $this->itemB->id);
    expect($itemB['closing_quantity'])->toBe(10.0);
    expect($itemB['closing_amount'])->toBe(50.0);
    expect($itemB['godown_details'][0]['godown_name'])->toBe('Store');
    expect($itemB['godown_details'][0]['batch_details'][0]['batch_no'])->toBeNull();
    expect($itemB['godown_details'][0]['batch_details'][0]['quantity'])->toBe(10.0);
});

test('closingStock() running branch respects the reporting-period end date as the as-of date', function () {
    // Reporting period ends mid-FY — movements after this date are excluded.
    $this->userFiscalYear->update(['end_date' => '2025-09-30']);

    // Recreate the service so it picks up the updated reporting period.
    $service = new StockSummaryService(new UserFiscalYearService);

    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-12-20', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    $result = $service->closingStock();

    expect($result['source'])->toBe('running');
    expect($result['as_of_date'])->toBe('2025-09-30');
    expect($result['total_items'])->toBe(1);

    $item = $result['items'][0];
    expect($item['closing_quantity'])->toBe(100.0);
    // Average rate is valued only from inward entries up to the as-of date.
    expect($item['rate'])->toBe(10.0);
    expect($item['closing_amount'])->toBe(1000.0);
});

// ---------------------------------------------------------------------------
//  Frozen branch (CLSSK closing journal exists)
// ---------------------------------------------------------------------------

test('closingStock() returns the frozen closing journal entries when a CLSSK voucher exists', function () {
    // A movement that would produce 150 units if computed live — the frozen
    // journal (100 units) must take precedence over the running computation.
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 150, 10, $this->saleType, 'in', 'B1');

    $closingJournal = StockJournal::create([
        'journal_no' => 'CLSSK-1-20260601',
        'journal_date' => '2026-03-31',
        'type' => 'CLOSING',
        'remarks' => 'Stock closing for FY 2025-26',
    ]);

    $closingVoucher = Voucher::create([
        'voucher_no' => 'CLSSK-1-20260601',
        'voucher_date' => '2026-03-31',
        'voucher_type_id' => $this->clsskType->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'company_id' => 1,
        'stock_journal_id' => $closingJournal->id,
        'status' => 'active',
        'module' => 'system',
        'effects_stock' => true,
    ]);

    $entry = StockJournalEntry::create([
        'stock_journal_id' => $closingJournal->id,
        'entry_order' => 1,
        'stock_item_id' => $this->item->id,
        'stock_unit_id' => $this->unit->id,
        'actual_quantity' => 100,
        'billing_quantity' => 100,
        'rate' => 10,
        'rate_unit_id' => $this->unit->id,
        'amount' => 1000,
        'movement_type' => 'in',
    ]);

    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $entry->id,
        'entry_order' => 1,
        'godown_id' => $this->mainGodown->id,
        'batch_no' => 'B1',
        'actual_quantity' => 100,
        'billing_quantity' => 100,
        'rate' => 10,
        'amount' => 1000,
        'movement_type' => 'in',
    ]);

    $result = $this->service->closingStock();

    expect($result['source'])->toBe('closing_journal');
    expect($result['closing_voucher_id'])->toBe($closingVoucher->id);
    expect($result['closing_voucher_no'])->toBe('CLSSK-1-20260601');
    expect($result['closing_date'])->toBe('2026-03-31');
    expect($result['as_of_date'])->toBeNull();
    expect($result['total_items'])->toBe(1);

    $item = $result['items'][0];
    expect($item['item_id'])->toBe($this->item->id);
    expect($item['closing_quantity'])->toBe(100.0);
    expect($item['closing_amount'])->toBe(1000.0);
    expect($item['rate'])->toBe(10.0);

    $godown = $item['godown_details'][0];
    expect($godown['godown_name'])->toBe('Main Godown');
    expect($godown['closing_quantity'])->toBe(100.0);
    expect($godown['closing_amount'])->toBe(1000.0);

    $batch = $godown['batch_details'][0];
    expect($batch['batch_no'])->toBe('B1');
    expect($batch['quantity'])->toBe(100.0);
    expect($batch['rate'])->toBe(10.0);
    expect($batch['amount'])->toBe(1000.0);
});

test('closingStock() returns empty totals when there are no movements', function () {
    $result = $this->service->closingStock();

    expect($result['source'])->toBe('running');
    expect($result['total_items'])->toBe(0);
    expect($result['total_quantity'])->toBe(0.0);
    expect($result['total_amount'])->toBe(0.0);
    expect($result['items'])->toBe([]);
});

test('closingStock() excludes items whose movements net to zero', function () {
    // Item A: +100 in / -100 out → net zero → excluded from the report
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-06-16', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'out', 'B1');

    // Item B: +10 in → net positive → stays
    createStockMovement('SALE-0003', '2025-06-17', $this->itemB, $this->unit, $this->storeGodown, $this->fiscalYear->id, 10, 5, $this->saleType);

    $result = $this->service->closingStock();

    expect($result['source'])->toBe('running');
    expect($result['total_items'])->toBe(1);
    expect($result['total_quantity'])->toBe(10.0);
    expect($result['total_amount'])->toBe(50.0);

    $itemNames = array_column($result['items'], 'item_name');
    expect($itemNames)->not->toContain('Item A');
    expect($itemNames)->toContain('Item B');
});

// ---------------------------------------------------------------------------
//  Stock In Hand views respect the reporting-period as-of date
// ---------------------------------------------------------------------------

test('stockInHand() respects the reporting-period end date as the as-of date', function () {
    $this->userFiscalYear->update(['end_date' => '2025-09-30']);
    $service = new StockSummaryService(new UserFiscalYearService);

    // +100 before the as-of date (included), +50 after (excluded)
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-12-20', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    $result = collect($service->stockInHand())->firstWhere('item_id', $this->item->id);

    expect($result)->not->toBeNull();
    expect($result['opening_quantity'])->toBe(0.0);
    expect($result['inward_quantity'])->toBe(100.0);
    expect($result['outward_quantity'])->toBe(0.0);
    expect($result['closing_quantity'])->toBe(100.0);
});

test('stock_in_hand_item_wise() respects the reporting-period end date as the as-of date', function () {
    $this->userFiscalYear->update(['end_date' => '2025-09-30']);
    $service = new StockSummaryService(new UserFiscalYearService);

    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-12-20', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    $result = collect($service->stock_in_hand_item_wise())->firstWhere('item_id', $this->item->id);

    expect($result)->not->toBeNull();
    expect($result['closing_quantity'])->toBe(100.0);
    expect($result['inward_quantity'])->toBe(100.0);
    // Godown-level breakdown is also filtered to the as-of date
    expect($result['godown_details'][0]['closing_quantity'])->toBe(100.0);
});

test('stock_in_hand_godown_wise() respects the reporting-period end date as the as-of date', function () {
    $this->userFiscalYear->update(['end_date' => '2025-09-30']);
    $service = new StockSummaryService(new UserFiscalYearService);

    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-12-20', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    $result = collect($service->stock_in_hand_godown_wise())->firstWhere('godown_id', $this->mainGodown->id);

    expect($result)->not->toBeNull();
    expect($result['inward_quantity'])->toBe(100.0);
    expect($result['closing_quantity'])->toBe(100.0);
    expect($result['item_details'][0]['closing_quantity'])->toBe(100.0);
});

test('stock_in_hand_voucher_wise() respects the reporting-period end date as the as-of date', function () {
    $this->userFiscalYear->update(['end_date' => '2025-09-30']);
    $service = new StockSummaryService(new UserFiscalYearService);

    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-12-20', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    $result = collect($service->stock_in_hand_voucher_wise())->firstWhere('item_id', $this->item->id);

    expect($result)->not->toBeNull();
    expect($result['closing_quantity'])->toBe(100.0);
    // Only the pre-as-of voucher appears in the voucher breakdown
    $voucherNos = array_column($result['voucher_details'], 'voucher_no');
    expect($voucherNos)->toBe(['SALE-0001']);
});

// ---------------------------------------------------------------------------
//  Opening balance: 9010 at FY start, recalculated for mid-year periods
// ---------------------------------------------------------------------------

test('stockInHand() opening balance is the 9010 (OPNSK) opening voucher when the reporting period starts on the fiscal year first date', function () {
    // Reporting period defaults to the full FY → starts on FY start (2025-04-01).
    createOpeningStockMovement('OPNSK-0001', '2025-04-01', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->opnskType);
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    $result = collect($this->service->stockInHand())->firstWhere('item_id', $this->item->id);

    expect($result)->not->toBeNull();
    expect($result['opening_quantity'])->toBe(100.0);
    expect($result['opening_amount'])->toBe(1000.0);
    expect($result['inward_quantity'])->toBe(50.0);
    expect($result['closing_quantity'])->toBe(150.0);
});

test('stockInHand() recalculates the opening balance for a mid-year reporting period including the 9010 opening voucher', function () {
    $this->userFiscalYear->update([
        'start_date' => '2025-07-01',
        'end_date' => '2025-09-30',
    ]);
    $service = new StockSummaryService(new UserFiscalYearService);

    // Before the reporting period: 9010 opening +100, purchase +50, sale -30 → opening = 120
    createOpeningStockMovement('OPNSK-0001', '2025-04-01', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->opnskType);
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0002', '2025-06-20', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 30, 10, $this->saleType, 'out', 'B1');

    // Within the period: purchase +20, sale -10 → inward = 20, outward = 10
    createStockMovement('SALE-0003', '2025-08-01', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 20, 12, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0004', '2025-09-05', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 10, 12, $this->saleType, 'out', 'B1');

    $result = collect($service->stockInHand())->firstWhere('item_id', $this->item->id);

    expect($result)->not->toBeNull();
    expect($result['opening_quantity'])->toBe(120.0);
    expect($result['inward_quantity'])->toBe(20.0);
    expect($result['outward_quantity'])->toBe(10.0);
    expect($result['closing_quantity'])->toBe(130.0);
});

test('stock_in_hand_godown_wise() recalculates the opening balance for a mid-year reporting period including the 9010 opening voucher', function () {
    $this->userFiscalYear->update([
        'start_date' => '2025-07-01',
        'end_date' => '2025-09-30',
    ]);
    $service = new StockSummaryService(new UserFiscalYearService);

    // Before the period: 9010 +100, purchase +50 → opening = 150
    createOpeningStockMovement('OPNSK-0001', '2025-04-01', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->opnskType);
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    // Within the period: +20 in, -10 out
    createStockMovement('SALE-0002', '2025-08-01', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 20, 12, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0003', '2025-08-10', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 10, 12, $this->saleType, 'out', 'B1');

    $result = collect($service->stock_in_hand_godown_wise())->firstWhere('godown_id', $this->mainGodown->id);

    expect($result)->not->toBeNull();
    expect($result['opening_quantity'])->toBe(150.0);
    expect($result['inward_quantity'])->toBe(20.0);
    expect($result['outward_quantity'])->toBe(10.0);
    expect($result['closing_quantity'])->toBe(160.0);
    expect($result['item_details'][0]['opening_quantity'])->toBe(150.0);
    expect($result['item_details'][0]['closing_quantity'])->toBe(160.0);
});

test('stock_in_hand_zone_wise() recalculates the opening balance for a mid-year reporting period including the 9010 opening voucher', function () {
    $this->userFiscalYear->update([
        'start_date' => '2025-07-01',
        'end_date' => '2025-09-30',
    ]);
    $service = new StockSummaryService(new UserFiscalYearService);

    $zone = Godown::create(['name' => 'Zone A', 'code' => 'ZONEA', 'storage_unit_type' => 'zone']);
    $zoneGodown = Godown::create(['name' => 'Zone Godown', 'code' => 'ZG1', 'parent_id' => $zone->id]);

    // Before the period: 9010 +100, purchase +50 → opening = 150
    createOpeningStockMovement('OPNSK-0001', '2025-04-01', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 100, 10, $this->opnskType);
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    // Within the period: +20 in, -10 out
    createStockMovement('SALE-0002', '2025-08-01', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 20, 12, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0003', '2025-08-10', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 10, 12, $this->saleType, 'out', 'B1');

    $result = collect($service->stock_in_hand_zone_wise())->firstWhere('zone_id', $zone->id);

    expect($result)->not->toBeNull();
    expect($result['opening_quantity'])->toBe(150.0);
    expect($result['inward_quantity'])->toBe(20.0);
    expect($result['outward_quantity'])->toBe(10.0);
    expect($result['closing_quantity'])->toBe(160.0);
    expect($result['godowns'][0]['opening_quantity'])->toBe(150.0);
    expect($result['godowns'][0]['closing_quantity'])->toBe(160.0);
});

test('stock_in_hand_item_wise() recalculates the opening balance for a mid-year reporting period including the 9010 opening voucher', function () {
    $this->userFiscalYear->update([
        'start_date' => '2025-07-01',
        'end_date' => '2025-09-30',
    ]);
    $service = new StockSummaryService(new UserFiscalYearService);

    // Before the period: 9010 +100, purchase +50 → opening = 150
    createOpeningStockMovement('OPNSK-0001', '2025-04-01', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->opnskType);
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 50, 12, $this->saleType, 'in', 'B1');

    // Within the period: +20 in, -10 out
    createStockMovement('SALE-0002', '2025-08-01', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 20, 12, $this->saleType, 'in', 'B1');
    createStockMovement('SALE-0003', '2025-08-10', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 10, 12, $this->saleType, 'out', 'B1');

    $result = collect($service->stock_in_hand_item_wise())->firstWhere('item_id', $this->item->id);

    expect($result)->not->toBeNull();
    expect($result['opening_quantity'])->toBe(150.0);
    expect($result['inward_quantity'])->toBe(20.0);
    expect($result['outward_quantity'])->toBe(10.0);
    expect($result['closing_quantity'])->toBe(160.0);
    expect($result['godown_details'][0]['opening_quantity'])->toBe(150.0);
    expect($result['godown_details'][0]['closing_quantity'])->toBe(160.0);
});

test('stock_in_hand_item_wise() aggregates correctly across chunk boundaries', function () {
    // More items than the 200-row chunk size — forces multiple chunks.
    foreach (range(1, 205) as $i) {
        $bulkItem = StockItem::create(['name' => "Bulk Item {$i}", 'code' => "BULKITM{$i}", 'stock_unit_id' => $this->unit->id]);
        createStockMovement("BULK-{$i}", '2025-06-15', $bulkItem, $this->unit, $this->mainGodown, $this->fiscalYear->id, 1, 10, $this->saleType, 'in', 'B1');
    }

    $result = $this->service->stock_in_hand_item_wise();

    // 205 bulk items + the beforeEach 'Item A' and 'Item B' (which have no movements)
    expect($result)->toHaveCount(207);
    expect(array_sum(array_column($result, 'closing_quantity')))->toBe(205.0);
    expect(array_sum(array_column($result, 'inward_quantity')))->toBe(205.0);
});

test('stock_in_hand_godown_wise() aggregates correctly across chunk boundaries', function () {
    // More movement rows than the 200-row chunk size — forces multiple chunks.
    foreach (range(1, 205) as $i) {
        createStockMovement("BULK-{$i}", '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 1, 10, $this->saleType, 'in', 'B1');
    }

    $result = collect($this->service->stock_in_hand_godown_wise())->firstWhere('godown_id', $this->mainGodown->id);

    expect($result)->not->toBeNull();
    expect($result['inward_quantity'])->toBe(205.0);
    expect($result['outward_quantity'])->toBe(0);
    expect($result['closing_quantity'])->toBe(205.0);
    expect($result['item_details'][0]['closing_quantity'])->toBe(205.0);
});

test('stock_in_hand_zone_wise() aggregates correctly across chunk boundaries', function () {
    $zone = Godown::create(['name' => 'Zone A', 'code' => 'ZONEA', 'storage_unit_type' => 'zone']);
    $zoneGodown = Godown::create(['name' => 'Zone Godown', 'code' => 'ZG1', 'parent_id' => $zone->id]);

    // More movement rows than the 200-row chunk size — forces multiple chunks.
    foreach (range(1, 205) as $i) {
        createStockMovement("BULK-{$i}", '2025-06-15', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 1, 10, $this->saleType, 'in', 'B1');
    }

    $result = collect($this->service->stock_in_hand_zone_wise())->firstWhere('zone_id', $zone->id);

    expect($result)->not->toBeNull();
    expect($result['inward_quantity'])->toBe(205.0);
    expect($result['closing_quantity'])->toBe(205.0);
    expect($result['godowns'][0]['closing_quantity'])->toBe(205.0);
});

test('stock_in_hand_voucher_wise() aggregates correctly across chunk boundaries', function () {
    // More items than the 200-row chunk size — forces multiple chunks.
    foreach (range(1, 205) as $i) {
        $bulkItem = StockItem::create(['name' => "Bulk Item {$i}", 'code' => "BULKITM{$i}", 'stock_unit_id' => $this->unit->id]);
        createStockMovement("BULK-{$i}", '2025-06-15', $bulkItem, $this->unit, $this->mainGodown, $this->fiscalYear->id, 1, 10, $this->saleType, 'in', 'B1');
    }

    $result = $this->service->stock_in_hand_voucher_wise();

    // 205 bulk items + the beforeEach 'Item A' and 'Item B' (which have no movements)
    expect($result)->toHaveCount(207);
    expect(array_sum(array_column($result, 'closing_quantity')))->toBe(205.0);
    expect(array_sum(array_column($result, 'inward_quantity')))->toBe(205.0);

    // A cross-chunk item carries exactly one voucher detail line
    $bulkRow = collect($result)->firstWhere('item_name', 'Bulk Item 205');
    expect($bulkRow['voucher_details'])->toHaveCount(1);
    expect($bulkRow['voucher_details'][0]['voucher_no'])->toBe('BULK-205');
    expect($bulkRow['voucher_details'][0]['inward_quantity'])->toBe(1.0);

    // Items with no movements are preserved across chunks (empty voucher breakdown)
    $emptyRow = collect($result)->firstWhere('item_name', 'Item A');
    expect($emptyRow['closing_quantity'])->toBe(0.0);
    expect($emptyRow['voucher_details'])->toBe([]);
});

// ---------------------------------------------------------------------------
//  Resource output (camelCase)
// ---------------------------------------------------------------------------

test('closingStock() response is camelCased end-to-end through ClosingStockResource', function () {
    createStockMovement('SALE-0001', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, 100, 10, $this->saleType, 'in', 'B1');

    $data = $this->service->closingStock();
    $payload = (new ClosingStockResource($data, 'Closing stock retrieved successfully.'))
        ->response()
        ->getData(true);

    expect($payload['success'])->toBeTrue();

    $body = $payload['data'];
    expect($body)->toHaveKeys([
        'source', 'asOfDate', 'closingVoucherId', 'closingVoucherNo',
        'closingDate', 'fiscalYear', 'totalItems', 'totalQuantity', 'totalAmount', 'items',
    ]);
    expect($body['source'])->toBe('running');
    expect($body['asOfDate'])->toBe('2026-03-31');
    expect($body['fiscalYear'])->toHaveKeys(['id', 'name', 'startDate', 'endDate']);

    $item = $body['items'][0];
    expect($item)->toHaveKeys([
        'itemId', 'itemName', 'unitCode', 'unitName', 'noOfDecimalPlaces',
        'closingQuantity', 'closingAmount', 'rate', 'godownDetails',
    ]);

    $godown = $item['godownDetails'][0];
    expect($godown)->toHaveKeys([
        'godownId', 'godownName', 'godownCode', 'closingQuantity', 'closingAmount', 'batchDetails',
    ]);

    $batch = $godown['batchDetails'][0];
    expect($batch)->toHaveKeys(['batchNo', 'mfgDate', 'expiryDate', 'quantity', 'amount', 'rate']);
});
