<?php

/**
 * Batch Lifeline — the unbounded history of one batch number: every line
 * across items, godowns and fiscal years, with running balances, opening
 * restatements, closing-snapshot markers, transfers and year subtotals.
 *
 * The two helpers below are intentionally self-contained (unique names) so
 * this file runs on its own as well as alongside the other StockSummary tests.
 */

use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockSummary\Services\StockSummaryService;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\UserFiscalYear\Services\UserFiscalYearService;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * One stock-journal line (voucher → journal → entry → godown entry) for the
 * lifeline tests. `$openingJournal` books an OPNSK opening-stock journal.
 */
function createLifelineLine(
    string $voucherNo,
    string $voucherDate,
    StockItem $item,
    StockUnit $unit,
    Godown $godown,
    int $fiscalYearId,
    VoucherType $voucherType,
    float $quantity,
    float $rate,
    string $movementType = 'in',
    ?string $batchNo = null,
    bool $openingJournal = false,
): Voucher {
    $amount = round($quantity * $rate, 2);

    $voucher = Voucher::create([
        'voucher_no' => $voucherNo,
        'voucher_date' => $voucherDate,
        'voucher_type_id' => $voucherType->id,
        'fiscal_year_id' => $fiscalYearId,
        'company_id' => 1,
        'module' => $openingJournal ? 'opening_stock' : 'purchase',
        'status' => 'active',
    ]);

    $journal = StockJournal::create([
        'journal_no' => $voucherNo,
        'journal_date' => $voucherDate,
        'type' => $openingJournal ? 'OPNSK' : ($movementType === 'in' ? 'PURCHASE' : 'SALE'),
        'remarks' => 'Batch lifeline test line',
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

/** Second godown leg on an existing line — the receiving side of a transfer. */
function addLifelineGodownLeg(
    Voucher $voucher,
    Godown $godown,
    float $quantity,
    string $movementType,
): StockJournalGodownEntry {
    $entry = StockJournalEntry::where('stock_journal_id', $voucher->stock_journal_id)
        ->orderBy('entry_order')
        ->firstOrFail();

    return StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $entry->id,
        'entry_order' => 2,
        'godown_id' => $godown->id,
        'batch_no' => 'B1',
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'rate' => 10,
        'amount' => round($quantity * 10, 2),
        'movement_type' => $movementType,
    ]);
}

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Batch Lifeline Test User',
        'email' => 'batch-lifeline-test@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    // Two fiscal years so the lifeline has to span a year boundary.
    $this->fy1 = FiscalYear::create([
        'name' => 'FY 2024-25',
        'start_date' => '2024-04-01',
        'end_date' => '2025-03-31',
        'status' => 'active',
        'company_id' => 1,
    ]);
    $this->fy2 = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
        'status' => 'active',
        'company_id' => 1,
    ]);

    UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fy2->id,
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
    ]);

    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->main = Godown::create(['name' => 'Main Godown', 'code' => 'MAIN']);
    $this->annexe = Godown::create(['name' => 'Annexe Godown', 'code' => 'ANX']);
    $this->item = StockItem::create(['name' => 'Item A', 'code' => 'ITEMA', 'stock_unit_id' => $this->unit->id]);
    $this->itemB = StockItem::create(['name' => 'Item B', 'code' => 'ITEMB', 'stock_unit_id' => $this->unit->id]);

    $this->category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);
    $this->purchaseType = VoucherType::create([
        'name' => 'Purchase',
        'code' => 'PURC',
        'voucher_category_id' => $this->category->id,
        'is_system' => true,
    ]);
    $this->saleType = VoucherType::create([
        'name' => 'Sales',
        'code' => 'SALE',
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

/** FY1 purchase + sale, then FY2 opening journal, purchase and sale. */
function seedTwoYearLifeline(object $test): void
{
    createLifelineLine('FY1-PURC', '2024-05-10', $test->item, $test->unit, $test->main, $test->fy1->id, $test->purchaseType, 100, 10, 'in', 'B1');
    createLifelineLine('FY1-SALE', '2024-06-01', $test->item, $test->unit, $test->main, $test->fy1->id, $test->saleType, 40, 12, 'out', 'B1');
    createLifelineLine('FY2-OPEN', '2025-04-01', $test->item, $test->unit, $test->main, $test->fy2->id, $test->opnskType, 60, 10, 'in', 'B1', true);
    createLifelineLine('FY2-PURC', '2025-06-01', $test->item, $test->unit, $test->main, $test->fy2->id, $test->purchaseType, 20, 11, 'in', 'B1');
    createLifelineLine('FY2-SALE', '2025-07-01', $test->item, $test->unit, $test->main, $test->fy2->id, $test->saleType, 50, 12, 'out', 'B1');
}

test('getBatchLifeline() lists the whole history chronologically with running balances', function () {
    seedTwoYearLifeline($this);

    $lifeline = $this->service->getBatchLifeline(['batch_no' => 'B1']);

    expect(array_column($lifeline['rows'], 'voucherNo'))->toBe([
        'FY1-PURC',
        'FY1-SALE',
        'FY2-OPEN',
        'FY2-PURC',
        'FY2-SALE',
    ]);

    // The FY1 receipt that the FY2 report would have swallowed into Opening
    // stays visible, and the running balance flows across the year boundary.
    expect(array_column($lifeline['rows'], 'runningBalance'))->toBe([
        100.0,
        60.0,
        60.0,
        80.0,
        30.0,
    ]);

    expect(array_column($lifeline['rows'], 'kind'))->toBe([
        'movement',
        'movement',
        'opening',
        'movement',
        'movement',
    ]);

    // The FY2 opening journal only restates the carried-forward 60 — counting
    // it again would double the stock.
    $opening = $lifeline['rows'][2];
    expect($opening['restatesOpening'])->toBeTrue();
    expect($opening['applied'])->toBeFalse();

    expect($lifeline['totals'])->toMatchArray([
        'rowCount' => 5,
        'movementCount' => 4,
        'openingRowCount' => 1,
        'restatedOpeningCount' => 1,
        'bucketCount' => 1,
        'closingQuantity' => 30.0,
        'firstDate' => '2024-05-10',
        'lastDate' => '2025-07-01',
        'outQuantity' => 90.0,
    ]);
    expect($lifeline['totals']['inValue'])->toBe(1000.0 + 220.0);
});

test('getBatchLifeline() marks fiscal years and subtotals each one', function () {
    seedTwoYearLifeline($this);

    $years = $this->service->getBatchLifeline(['batch_no' => 'B1'])['years'];

    expect(array_column($years, 'name'))->toBe(['FY 2024-25', 'FY 2025-26']);
    expect($years[0]['startDate'])->toBe('2024-04-01');
    expect($years[1]['endDate'])->toBe('2026-03-31');

    // FY1: nothing carried in, 100 in, 40 out, 60 carried out.
    expect($years[0])->toMatchArray([
        'carriedIn' => 0.0,
        'inQuantity' => 100.0,
        'outQuantity' => 40.0,
        'carriedOut' => 60.0,
        'rowCount' => 2,
        'movementCount' => 2,
        'openingRestated' => false,
    ]);

    // FY2 opens on FY1's closing balance and keeps going from there.
    expect($years[1])->toMatchArray([
        'carriedIn' => 60.0,
        'carriedOut' => 30.0,
        'inQuantity' => 20.0,
        'outQuantity' => 50.0,
        'openingQuantity' => 60.0,
        'openingRestated' => true,
        'rowCount' => 3,
    ]);
    expect($years[1]['buckets'][0]['godownName'])->toBe('Main Godown');
    expect($years[1]['hasMixedUnits'])->toBeFalse();
});

test('getBatchLifeline() treats CLOSING journals as markers that never move the balance', function () {
    createLifelineLine('PURC-1', '2025-06-01', $this->item, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 30, 10, 'in', 'B1');
    $closing = createLifelineLine('CLOSE-1', '2026-03-31', $this->item, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 30, 10, 'in', 'B1');
    StockJournal::where('id', $closing->stock_journal_id)->update(['type' => 'CLOSING']);

    $lifeline = $this->service->getBatchLifeline(['batch_no' => 'B1']);

    expect(array_column($lifeline['rows'], 'kind'))->toBe(['movement', 'closing']);
    expect(array_column($lifeline['rows'], 'runningBalance'))->toBe([30.0, 30.0]);
    expect($lifeline['rows'][1]['applied'])->toBeFalse();
    expect($lifeline['buckets'][0]['snapshotQuantity'])->toBe(30.0);
    expect($lifeline['totals']['snapshotRowCount'])->toBe(1);
    // The snapshot is context, not a receipt.
    expect($lifeline['totals']['inQuantity'])->toBe(30.0);

    $withoutSnapshots = $this->service->getBatchLifeline([
        'batch_no' => 'B1',
        'include_snapshots' => false,
    ]);
    expect($withoutSnapshots['totals']['rowCount'])->toBe(1);
    expect($withoutSnapshots['meta']['includesClosingSnapshots'])->toBeFalse();
});

test('getBatchLifeline() keeps one balance per item+godown when a batch number is reused', function () {
    createLifelineLine('A-PURC', '2025-06-01', $this->item, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 100, 10, 'in', 'SHARED');
    createLifelineLine('B-PURC', '2025-06-02', $this->itemB, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 7, 100, 'in', 'SHARED');
    createLifelineLine('A-SALE', '2025-06-03', $this->item, $this->unit, $this->main, $this->fy2->id, $this->saleType, 25, 12, 'out', 'SHARED');

    $lifeline = $this->service->getBatchLifeline(['batch_no' => 'SHARED']);

    expect($lifeline['totals']['bucketCount'])->toBe(2);
    expect(array_column($lifeline['rows'], 'runningBalance'))->toBe([100.0, 7.0, 75.0]);
    expect(array_column($lifeline['buckets'], 'closingQuantity'))->toBe([75.0, 7.0]);
    // Quantities across buckets are in different units — the year flags it.
    expect($lifeline['years'][0]['hasMixedUnits'])->toBeTrue();

    $narrowed = $this->service->getBatchLifeline([
        'batch_no' => 'SHARED',
        'item_id' => $this->itemB->id,
    ]);
    expect($narrowed['totals']['rowCount'])->toBe(1);
    expect($narrowed['buckets'][0]['itemName'])->toBe('Item B');
});

test('getBatchLifeline() seeds the running balance when a from_date narrows the window', function () {
    seedTwoYearLifeline($this);

    $windowed = $this->service->getBatchLifeline([
        'batch_no' => 'B1',
        'from_date' => '2025-06-01',
    ]);

    // Only the lines from the window are listed…
    expect(array_column($windowed['rows'], 'voucherNo'))->toBe(['FY2-PURC', 'FY2-SALE']);
    // …but the balance carried in from the replayed history is real (60 + 20).
    expect(array_column($windowed['rows'], 'runningBalance'))->toBe([80.0, 30.0]);
    expect($windowed['meta']['seedRowCount'])->toBe(3);
    expect($windowed['meta']['seedTruncated'])->toBeFalse();
    expect($windowed['years'][0])->toMatchArray([
        'carriedIn' => 60.0,
        'carriedOut' => 30.0,
    ]);
});

test('getBatchLifeline() pairs the two legs of a godown transfer', function () {
    $transfer = createLifelineLine('TRF-1', '2025-06-10', $this->item, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 10, 10, 'out', 'B1');
    addLifelineGodownLeg($transfer, $this->annexe, 10, 'in');

    $lifeline = $this->service->getBatchLifeline(['batch_no' => 'B1']);

    expect($lifeline['totals']['rowCount'])->toBe(2);
    expect($lifeline['totals']['transferCount'])->toBe(2);

    $out = $lifeline['rows'][0];
    $in = $lifeline['rows'][1];
    expect($out['transfer'])->toBeTrue();
    expect($out['transferPeerGodownName'])->toBe('Annexe Godown');
    expect($in['transfer'])->toBeTrue();
    expect($in['transferPeerGodownName'])->toBe('Main Godown');
    // Each godown keeps its own running balance.
    expect($out['runningBalance'])->toBe(-10.0);
    expect($in['runningBalance'])->toBe(10.0);
});

test('getBatchLifeline() narrows by godown and truncates loudly', function () {
    createLifelineLine('MAIN-1', '2025-06-01', $this->item, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 10, 10, 'in', 'B1');
    createLifelineLine('ANX-1', '2025-06-02', $this->item, $this->unit, $this->annexe, $this->fy2->id, $this->purchaseType, 5, 10, 'in', 'B1');

    $mainOnly = $this->service->getBatchLifeline([
        'batch_no' => 'B1',
        'godown_id' => $this->main->id,
    ]);
    expect(array_column($mainOnly['rows'], 'voucherNo'))->toBe(['MAIN-1']);

    $capped = $this->service->getBatchLifeline(['batch_no' => 'B1', 'limit' => 1]);
    expect($capped['totals']['rowCount'])->toBe(1);
    expect($capped['meta']['truncated'])->toBeTrue();
    expect($capped['meta']['limit'])->toBe(1);
});

test('getBatchLifeline() returns an empty lifeline instead of the whole ledger without a batch number', function () {
    createLifelineLine('PURC-1', '2025-06-01', $this->item, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 10, 10, 'in', 'B1');

    $lifeline = $this->service->getBatchLifeline([]);

    expect($lifeline['rows'])->toBe([]);
    expect($lifeline['years'])->toBe([]);
    expect($lifeline['buckets'])->toBe([]);
    expect($lifeline['totals']['rowCount'])->toBe(0);
    expect($lifeline['meta']['filters']['batchNo'])->toBe('');
});

test('the batch lifeline route is protected and requires a batch number', function () {
    // No token: the jwt.cookies middleware rejects the request (which also
    // proves the route is registered).
    $this->getJson('/api/stock_summaries/batch_lifeline')->assertStatus(401);

    $token = JWTAuth::fromUser($this->user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stock_summaries/batch_lifeline')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['batch_no']);

    // With a batch number the same route answers normally.
    createLifelineLine('PURC-1', '2025-06-01', $this->item, $this->unit, $this->main, $this->fy2->id, $this->purchaseType, 10, 10, 'in', 'B1');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stock_summaries/batch_lifeline?batch_no=B1')
        ->assertOk()
        ->assertJsonPath('data.batchNo', 'B1')
        ->assertJsonPath('data.totals.rowCount', 1)
        ->assertJsonPath('data.buckets.0.godownName', 'Main Godown');
});
