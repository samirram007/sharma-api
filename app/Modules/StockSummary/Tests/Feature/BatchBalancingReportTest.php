<?php

/**
 * Batch Balancing report — the inspection layer added on top of the movement
 * trail: batch-level paging / sorting, integrity flags + issue filters,
 * expiry ageing, valuation and the voucher-type facet.
 *
 * The movement helper below is intentionally self-contained (unique name) so
 * this file can run on its own as well as alongside ClosingStockTest.php.
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

/**
 * One stock movement (voucher → stock journal → entry → godown entry) for the
 * batch-balancing tests, optionally an OPNSK opening-stock journal line and
 * optionally carrying mfg / expiry dates.
 */
function createBatchBalancingMovement(
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
    ?string $mfgDate = null,
    ?string $expiryDate = null,
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
        'remarks' => 'Batch balancing test movement',
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
        'mfg_date' => $mfgDate,
        'expiry_date' => $expiryDate,
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
        'name' => 'Batch Balancing Test User',
        'email' => 'batch-balancing-test@example.com',
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

    $this->userFiscalYear = UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
    ]);

    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->mainGodown = Godown::create(['name' => 'Main Godown', 'code' => 'MAIN']);
    $this->item = StockItem::create(['name' => 'Item A', 'code' => 'ITEMA', 'stock_unit_id' => $this->unit->id]);
    $this->itemB = StockItem::create(['name' => 'Item B', 'code' => 'ITEMB', 'stock_unit_id' => $this->unit->id]);

    $this->category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);
    $this->saleType = VoucherType::create([
        'name' => 'Sales',
        'code' => 'SALE',
        'voucher_category_id' => $this->category->id,
        'is_system' => true,
    ]);
    $this->purchaseType = VoucherType::create([
        'name' => 'Purchase',
        'code' => 'PURC',
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
//  Paging & sorting
// ---------------------------------------------------------------------------

test('getBatchMovements() pages batches server-side, totals every match and loads trails only for the page', function () {
    foreach (range(1, 5) as $i) {
        createBatchBalancingMovement(
            "PURC-{$i}",
            '2025-06-15',
            $this->item,
            $this->unit,
            $this->mainGodown,
            $this->fiscalYear->id,
            $this->saleType,
            $i * 10,
            10,
            'in',
            'B0'.$i,
        );
    }

    $page1 = $this->service->getBatchMovements(['per_page' => 2, 'page' => 1, 'sort_by' => 'batch']);

    expect($page1['pagination'])->toBe([
        'page' => 1,
        'perPage' => 2,
        'total' => 5,
        'totalPages' => 3,
        'hasMore' => true,
    ]);
    expect(array_column($page1['batches'], 'batchNo'))->toBe(['B01', 'B02']);
    // Trail rows are present for the page's batches.
    expect($page1['batches'][0]['movements'])->toHaveCount(1);
    expect($page1['batches'][0]['movementCount'])->toBe(1);

    // Totals describe every matching batch, not just the page.
    expect($page1['totals']['batchCount'])->toBe(5);
    expect($page1['totals']['totalInward'])->toBe(150.0);
    expect($page1['totals']['totalClosing'])->toBe(150.0);

    $page3 = $this->service->getBatchMovements(['per_page' => 2, 'page' => 3, 'sort_by' => 'batch']);
    expect(array_column($page3['batches'], 'batchNo'))->toBe(['B05']);
    expect($page3['pagination']['hasMore'])->toBeFalse();

    // Out-of-range pages clamp to the last page rather than returning nothing.
    $past = $this->service->getBatchMovements(['per_page' => 2, 'page' => 99, 'sort_by' => 'batch']);
    expect($past['pagination']['page'])->toBe(3);
    expect(array_column($past['batches'], 'batchNo'))->toBe(['B05']);

    // per_page = 0 returns the whole result set (no paging).
    $everything = $this->service->getBatchMovements(['per_page' => 0]);
    expect($everything['batches'])->toHaveCount(5);
    expect($everything['pagination']['perPage'])->toBe(0);
    expect($everything['pagination']['totalPages'])->toBe(1);
});

test('getBatchMovements() sorts batches by the selected column and direction', function () {
    createBatchBalancingMovement('PURC-1', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 10, 10, 'in', 'SMALL');
    createBatchBalancingMovement('PURC-2', '2025-06-16', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 90, 10, 'in', 'LARGE');

    $desc = $this->service->getBatchMovements(['sort_by' => 'closing', 'sort_dir' => 'desc']);
    expect(array_column($desc['batches'], 'batchNo'))->toBe(['LARGE', 'SMALL']);
    expect($desc['sort'])->toBe(['by' => 'closing', 'dir' => 'desc']);

    $asc = $this->service->getBatchMovements(['sort_by' => 'closing', 'sort_dir' => 'asc']);
    expect(array_column($asc['batches'], 'batchNo'))->toBe(['SMALL', 'LARGE']);

    // Value ordering follows the closing valuation (qty × rate).
    $byValue = $this->service->getBatchMovements(['sort_by' => 'value', 'sort_dir' => 'desc']);
    expect(array_column($byValue['batches'], 'batchNo'))->toBe(['LARGE', 'SMALL']);
    expect($byValue['batches'][0]['closingValue'])->toBe(900.0);

    // Unknown sort keys fall back to the item ordering instead of erroring.
    $fallback = $this->service->getBatchMovements(['sort_by' => 'nonsense']);
    expect($fallback['sort']['by'])->toBe('item');
});

// ---------------------------------------------------------------------------
//  Valuation
// ---------------------------------------------------------------------------

test('getBatchMovements() values each batch and the totals across the report', function () {
    createBatchBalancingMovement('PURC-1', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 10, 100, 'in', 'B1');
    createBatchBalancingMovement('PURC-2', '2025-07-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 5, 120, 'in', 'B1');
    createBatchBalancingMovement('SALE-1', '2025-08-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 4, 120, 'out', 'B1');

    $result = $this->service->getBatchMovements();
    $batch = $result['batches'][0];

    expect($batch['closingQuantity'])->toBe(11.0);
    expect($batch['openingValue'])->toBe(0.0);
    expect($batch['inwardValue'])->toBe(1600.0);
    expect($batch['outwardValue'])->toBe(480.0);
    expect($batch['closingValue'])->toBe(1120.0);
    expect($batch['averageRate'])->toBe(101.8182);

    expect($result['totals']['totalOpeningValue'])->toBe(0.0);
    expect($result['totals']['totalInwardValue'])->toBe(1600.0);
    expect($result['totals']['totalOutwardValue'])->toBe(480.0);
    expect($result['totals']['totalClosingValue'])->toBe(1120.0);
});

// ---------------------------------------------------------------------------
//  Integrity: negative stock, opening-voucher variance, idle + carry-forward
// ---------------------------------------------------------------------------

test('getBatchMovements() flags negative balances and can filter to them', function () {
    createBatchBalancingMovement('PURC-1', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 10, 10, 'in', 'NEG');
    createBatchBalancingMovement('SALE-1', '2025-07-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 15, 10, 'out', 'NEG');
    createBatchBalancingMovement('PURC-2', '2025-06-16', $this->itemB, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 10, 10, 'in', 'GOOD');

    $all = $this->service->getBatchMovements();

    $negative = collect($all['batches'])->firstWhere('batchNo', 'NEG');
    expect($negative['closingQuantity'])->toBe(-5.0);
    expect($negative['flags']['negativeClosing'])->toBeTrue();
    expect($negative['issues'])->toContain('negativeClosing');
    expect($all['totals']['negativeCount'])->toBe(1);
    expect($all['totals']['zeroClosingCount'])->toBe(0);

    $onlyNegative = $this->service->getBatchMovements(['issue' => 'negative']);
    expect($onlyNegative['totals']['batchCount'])->toBe(1);
    expect($onlyNegative['batches'][0]['batchNo'])->toBe('NEG');

    // in_stock_only drops everything that nets to zero or below.
    $inStock = $this->service->getBatchMovements(['in_stock_only' => true]);
    expect(array_column($inStock['batches'], 'batchNo'))->toBe(['GOOD']);
});

test('getBatchMovements() flags an opening that disagrees with the opening voucher', function () {
    // Mid-year window: the June purchase is pre-period, so it folds into
    // Opening while this FY's opening journal contributes nothing for it.
    createBatchBalancingMovement('PURC-1', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 20, 10, 'in', 'VARIANCE');
    // A batch the 9010 opening voucher does cover.
    createBatchBalancingMovement('OPNSK-1', '2025-04-01', $this->itemB, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->opnskType, 30, 10, 'in', 'COVERED', null, null, true);

    $window = ['from_date' => '2025-07-01', 'to_date' => '2025-09-30'];
    $result = $this->service->getBatchMovements($window);
    $byBatch = collect($result['batches'])->keyBy('batchNo');

    expect($byBatch['VARIANCE']['openingQuantity'])->toBe(20.0);
    expect($byBatch['VARIANCE']['openingAsPerJournal'])->toBe(0.0);
    expect($byBatch['VARIANCE']['prePeriodNet'])->toBe(20.0);
    expect($byBatch['VARIANCE']['flags']['journalVariance'])->toBeTrue();

    // The 9010 line dated 1-Apr is still the journal part of Opening even
    // though it sits before a July–September window.
    expect($byBatch['COVERED']['openingAsPerJournal'])->toBe(30.0);
    expect($byBatch['COVERED']['flags']['journalVariance'])->toBeFalse();

    expect($result['totals']['journalVarianceCount'])->toBe(1);

    $only = $this->service->getBatchMovements($window + ['issue' => 'journal_variance']);
    expect(array_column($only['batches'], 'batchNo'))->toBe(['VARIANCE']);

    // Nothing in this window is clean: VARIANCE disagrees with the opening
    // voucher and COVERED has no movement of its own in July–September.
    expect($this->service->getBatchMovements($window + ['issue' => 'ok'])['batches'])->toBe([]);
    // Both batches are opening-only inside July–September (item order applies).
    $idle = $this->service->getBatchMovements($window + ['issue' => 'no_movement']);
    expect(array_column($idle['batches'], 'batchNo'))->toBe(['VARIANCE', 'COVERED']);
});

test('getBatchMovements() flags carry-forward openings and idle batches in a new fiscal year', function () {
    // FY1 history that never made it into an FY2 opening voucher.
    createBatchBalancingMovement('PURC-1', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 40, 10, 'in', 'CARRY');

    $fy2 = FiscalYear::create([
        'name' => 'FY 2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'status' => 'active',
        'company_id' => 1,
    ]);
    $this->userFiscalYear->update([
        'fiscal_year_id' => $fy2->id,
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
    ]);
    $service = new StockSummaryService(new UserFiscalYearService);

    // A batch FY2's opening voucher does cover, with real activity in the year.
    createBatchBalancingMovement('OPNSK-1', '2026-04-01', $this->itemB, $this->unit, $this->mainGodown, $fy2->id, $this->opnskType, 10, 10, 'in', 'COVERED', null, null, true);
    createBatchBalancingMovement('SALE-1', '2026-05-01', $this->itemB, $this->unit, $this->mainGodown, $fy2->id, $this->saleType, 4, 10, 'out', 'COVERED');

    $result = $service->getBatchMovements();
    $byBatch = collect($result['batches'])->keyBy('batchNo');

    expect($byBatch['CARRY']['flags']['carryForwardOpening'])->toBeTrue();
    // Prior-FY rows are balance-only — no opening detail trail is listed.
    expect($byBatch['CARRY']['openingMovements'])->toBe([]);
    expect($byBatch['CARRY']['movementCount'])->toBe(0);
    expect($byBatch['COVERED']['flags']['carryForwardOpening'])->toBeFalse();

    expect($result['totals']['carryForwardCount'])->toBe(1);
    expect($result['totals']['noMovementCount'])->toBe(1);

    $idle = $service->getBatchMovements(['issue' => 'no_movement']);
    expect(array_column($idle['batches'], 'batchNo'))->toBe(['CARRY']);

    $carryForward = $service->getBatchMovements(['issue' => 'carry_forward']);
    expect(array_column($carryForward['batches'], 'batchNo'))->toBe(['CARRY']);

    $ok = $service->getBatchMovements(['issue' => 'ok']);
    expect(array_column($ok['batches'], 'batchNo'))->toBe(['COVERED']);
});

// ---------------------------------------------------------------------------
//  Expiry ageing
// ---------------------------------------------------------------------------

test('getBatchMovements() classifies batches by expiry against the reporting window end', function () {
    $expiring = fn (string $batchNo, ?string $expiry) => createBatchBalancingMovement(
        'DATED-'.$batchNo,
        '2025-04-01',
        $this->item,
        $this->unit,
        $this->mainGodown,
        $this->fiscalYear->id,
        $this->saleType,
        10,
        10,
        'in',
        $batchNo,
        '2025-04-01',
        $expiry,
    );

    $expiring('EXPIRED', '2026-03-01');
    $expiring('SOON', '2026-04-20');
    $expiring('LATER', '2026-12-01');
    $expiring('NONE', null);

    $result = $this->service->getBatchMovements();
    $byBatch = collect($result['batches'])->keyBy('batchNo');

    // Ageing is measured against the window end (the FY end by default).
    expect($result['ageingAsOnDate'])->toBe('2026-03-31');
    expect($result['expiryWithinDays'])->toBe(90);

    expect($byBatch['EXPIRED']['expiryStatus'])->toBe('expired');
    expect($byBatch['EXPIRED']['daysToExpiry'])->toBe(-30);
    expect($byBatch['SOON']['expiryStatus'])->toBe('expiring');
    expect($byBatch['SOON']['daysToExpiry'])->toBe(20);
    expect($byBatch['LATER']['expiryStatus'])->toBe('valid');
    expect($byBatch['LATER']['daysToExpiry'])->toBe(245);
    expect($byBatch['LATER']['daysSinceMfg'])->toBe(364);
    expect($byBatch['NONE']['expiryStatus'])->toBe('none');
    expect($byBatch['NONE']['daysToExpiry'])->toBeNull();

    expect($result['totals']['expiredCount'])->toBe(1);
    expect($result['totals']['expiringCount'])->toBe(1);

    expect(array_column($this->service->getBatchMovements(['expiry_status' => 'expired'])['batches'], 'batchNo'))->toBe(['EXPIRED']);
    expect(array_column($this->service->getBatchMovements(['expiry_status' => 'expiring'])['batches'], 'batchNo'))->toBe(['SOON']);
    expect(array_column($this->service->getBatchMovements(['expiry_status' => 'valid'])['batches'], 'batchNo'))->toBe(['LATER']);
    expect(array_column($this->service->getBatchMovements(['expiry_status' => 'none'])['batches'], 'batchNo'))->toBe(['NONE']);

    // A tighter threshold moves SOON out of the expiring bucket.
    expect($this->service->getBatchMovements(['expiry_status' => 'expiring', 'expiry_within_days' => 10])['batches'])->toBe([]);
    // Unset/unknown expiry filters leave everything visible.
    expect($this->service->getBatchMovements(['expiry_status' => 'nonsense'])['totals']['batchCount'])->toBe(4);
});

// ---------------------------------------------------------------------------
//  Voucher-type facet / filter and the trail opt-out
// ---------------------------------------------------------------------------

test('getBatchMovements() filters by voucher type and reports the types present', function () {
    createBatchBalancingMovement('PURC-1', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->purchaseType, 10, 10, 'in', 'B1');
    createBatchBalancingMovement('SALE-1', '2025-07-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 2, 10, 'out', 'B1');

    $all = $this->service->getBatchMovements();
    expect(collect($all['voucherTypes'])->pluck('code')->sort()->values()->toArray())->toBe(['PURC', 'SALE']);

    $salesOnly = $this->service->getBatchMovements(['voucher_type_id' => $this->saleType->id]);
    $batch = $salesOnly['batches'][0];
    expect($batch['inwardQuantity'])->toBe(0.0);
    expect($batch['outwardQuantity'])->toBe(2.0);
    expect($batch['closingQuantity'])->toBe(-2.0);
    expect($salesOnly['voucherTypes'])->toHaveCount(1);
    expect($salesOnly['voucherTypes'][0]['code'])->toBe('SALE');
});

test('getBatchMovements() can skip the movement trail while keeping the aggregates', function () {
    createBatchBalancingMovement('PURC-1', '2025-06-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 10, 10, 'in', 'B1');
    createBatchBalancingMovement('SALE-1', '2025-07-15', $this->item, $this->unit, $this->mainGodown, $this->fiscalYear->id, $this->saleType, 4, 10, 'out', 'B1');

    $light = $this->service->getBatchMovements(['include_movements' => 'false']);
    $batch = $light['batches'][0];

    expect($batch['movements'])->toBe([]);
    expect($batch['openingMovements'])->toBe([]);
    expect($batch['movementCount'])->toBe(2);
    expect($batch['closingQuantity'])->toBe(6.0);
    expect($batch['closingValue'])->toBe(60.0);

    // With the trail requested (the default) both rows come back.
    $full = $this->service->getBatchMovements();
    expect($full['batches'][0]['movements'])->toHaveCount(2);
});
