<?php

use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Facades\VoucherFacade;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;

/**
 * Tests for GET /vouchers/opening-stock/previous-year-closing (the "Fetch
 * Previous Year Closing Stock" button on the Opening Stock screen).
 *
 * When the previous fiscal year has no frozen CLSSK closing journal (never
 * closed, or closed without stock), the endpoint must fall back to the
 * previous year's RUNNING balance computed live from stock movements, so
 * opening stock can still be pre-filled.
 */
beforeEach(function () {
    $this->companyId = 1;

    $this->user = User::create([
        'name' => 'Previous Year Closing Test User',
        'email' => 'prev-year-closing@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    $category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

    $this->voucherTypes = [];
    foreach (['CLSSK' => 'Closing Stock', 'SALE' => 'Sales'] as $code => $name) {
        $this->voucherTypes[$code] = VoucherType::create([
            'name' => $name,
            'code' => $code,
            'voucher_category_id' => $category->id,
            'is_system' => true,
        ]);
    }

    // --- Stock master ---
    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->godown = Godown::create(['name' => 'Main Godown', 'code' => 'MAIN']);
    $this->item = StockItem::create(['name' => 'Item A', 'code' => 'ITEMA', 'stock_unit_id' => $this->unit->id]);

    // --- Previous FY (2025-26, NOT closed → no CLSSK) + current FY (2026-27) ---
    $this->fy1 = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    $this->fy2 = FiscalYear::create([
        'name' => 'FY 2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    // The acting user is mapped to the CURRENT fiscal year.
    UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fy2->id,
        'start_date' => $this->fy2->start_date,
        'end_date' => $this->fy2->end_date,
    ]);

    // --- Source stock movement in the PREVIOUS FY: 100 KG @ 10 in MAIN ---
    $this->sourceVoucher = Voucher::create([
        'voucher_no' => 'SALE-0001',
        'voucher_date' => '2025-06-15',
        'voucher_type_id' => $this->voucherTypes['SALE']->id,
        'fiscal_year_id' => $this->fy1->id,
        'company_id' => $this->companyId,
        'module' => 'sales',
        'remarks' => 'Test sales voucher',
        'status' => 'active',
    ]);

    $this->sourceStockJournal = StockJournal::create([
        'journal_no' => 'SRC-0001',
        'journal_date' => '2025-06-15',
        'type' => 'in',
        'remarks' => 'Opening purchase',
    ]);
    $this->sourceVoucher->update(['stock_journal_id' => $this->sourceStockJournal->id]);

    $this->sourceStockEntry = StockJournalEntry::create([
        'stock_journal_id' => $this->sourceStockJournal->id,
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
        'stock_journal_entry_id' => $this->sourceStockEntry->id,
        'entry_order' => 1,
        'godown_id' => $this->godown->id,
        'actual_quantity' => 100,
        'billing_quantity' => 100,
        'rate' => 10,
        'amount' => 1000,
        'movement_type' => 'in',
    ]);
});

test('previous year closing falls back to the running balance when no CLSSK journal exists', function () {
    $result = VoucherFacade::getPreviousYearClosingStock();

    expect($result['source'])->toBe('running');
    expect($result['previousFiscalYear']['id'])->toBe($this->fy1->id);
    expect($result['previousFiscalYear']['name'])->toBe('FY 2025-26');
    expect($result['previousFiscalYear']['isClosed'])->toBeFalse();
    expect($result['closingVoucherNo'])->toBeNull();
    expect($result['closingDate'])->toBe('2026-03-31');

    // Synthetic closing voucher in the standard camelCase voucher shape
    $voucher = $result['closingVoucher'];
    expect($voucher)->not->toBeNull();
    expect($voucher['stockJournal']['stockJournalEntries'])->toHaveCount(1);

    $entry = $voucher['stockJournal']['stockJournalEntries'][0];
    expect($entry['stockItemId'])->toBe($this->item->id);
    expect($entry['stockItem']['name'])->toBe('Item A');
    expect((float) $entry['actualQuantity'])->toBe(100.0);
    expect((float) $entry['amount'])->toBe(1000.0);
    expect($entry['movementType'])->toBe('in');

    expect($entry['stockJournalGodownEntries'])->toHaveCount(1);
    $godownEntry = $entry['stockJournalGodownEntries'][0];
    expect($godownEntry['godownId'])->toBe($this->godown->id);
    expect($godownEntry['godown']['name'])->toBe('Main Godown');
    expect((float) $godownEntry['actualQuantity'])->toBe(100.0);
    expect((float) $godownEntry['amount'])->toBe(1000.0);

    // The synthetic payload carries the fields StockJournalEntryRequest marks
    // as required, so saving the pre-filled opening stock passes validation.
    expect($entry['itemCost'])->toBe(0);
    expect($entry['rateUnitId'])->toBe($this->unit->id);
    expect($entry['discountPercentage'])->toBe(0);
    expect($entry['discount'])->toBe(0);
});

test('previous year closing running balance expands one godown entry per batch', function () {
    // Add a second batch (B2) of 50 KG for the same item in the same godown.
    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $this->sourceStockEntry->id,
        'entry_order' => 2,
        'godown_id' => $this->godown->id,
        'batch_no' => 'B2',
        'actual_quantity' => 50,
        'billing_quantity' => 50,
        'rate' => 10,
        'amount' => 500,
        'movement_type' => 'in',
    ]);

    $result = VoucherFacade::getPreviousYearClosingStock();

    expect($result['source'])->toBe('running');
    $voucher = $result['closingVoucher'];
    $entry = $voucher['stockJournal']['stockJournalEntries'][0];

    // 100 (no batch) + 50 (B2) = 150 total, split across two godown rows.
    expect((float) $entry['actualQuantity'])->toBe(150.0);
    expect((float) $entry['amount'])->toBe(1500.0);
    expect($entry['stockJournalGodownEntries'])->toHaveCount(2);

    $batches = collect($entry['stockJournalGodownEntries']);
    expect($batches->pluck('batchNo')->all())->toContain('B2');
    $b2 = $batches->first(fn ($ge) => $ge['batchNo'] === 'B2');
    expect((float) $b2['actualQuantity'])->toBe(50.0);
    expect($b2['godownId'])->toBe($this->godown->id);
});

test('previous year closing returns the frozen CLSSK journal when one exists', function () {
    // Simulate a closed previous FY with a frozen closing stock voucher.
    $this->fy1->update(['closed_at' => now(), 'status' => 'inactive']);

    $clssk = Voucher::create([
        'voucher_no' => 'CLSSK-0001',
        'voucher_date' => '2026-03-31',
        'voucher_type_id' => $this->voucherTypes['CLSSK']->id,
        'fiscal_year_id' => $this->fy1->id,
        'company_id' => $this->companyId,
        'module' => 'system',
        'remarks' => 'Closing stock - FY 2025-26',
        'status' => 'active',
    ]);

    $closingJournal = StockJournal::create([
        'journal_no' => 'CLSSK-0001',
        'journal_date' => '2026-03-31',
        'type' => 'CLOSING',
        'remarks' => 'Closing stock',
    ]);
    $clssk->update(['stock_journal_id' => $closingJournal->id]);

    $closingEntry = StockJournalEntry::create([
        'stock_journal_id' => $closingJournal->id,
        'entry_order' => 1,
        'stock_item_id' => $this->item->id,
        'stock_unit_id' => $this->unit->id,
        'actual_quantity' => 100,
        'billing_quantity' => 100,
        'rate' => 10,
        'amount' => 1000,
        'movement_type' => 'in',
    ]);
    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $closingEntry->id,
        'entry_order' => 1,
        'godown_id' => $this->godown->id,
        'actual_quantity' => 100,
        'billing_quantity' => 100,
        'rate' => 10,
        'amount' => 1000,
        'movement_type' => 'in',
    ]);

    $result = VoucherFacade::getPreviousYearClosingStock();

    expect($result['source'])->toBe('closing_journal');
    expect($result['previousFiscalYear']['isClosed'])->toBeTrue();
    expect($result['closingVoucherNo'])->toBe('CLSSK-0001');

    // JSON round-trip mirrors the real API serialization (nested resources
    // only fully resolve during JSON encoding).
    $voucher = json_decode(json_encode($result['closingVoucher']), true);
    expect($voucher['voucherNo'])->toBe('CLSSK-0001');
    expect($voucher['stockJournal']['stockJournalEntries'])->toHaveCount(1);
    expect($voucher['stockJournal']['stockJournalEntries'][0]['stockItemId'])->toBe($this->item->id);
});

test('previous year closing returns no closing voucher when the previous year has no stock movements', function () {
    // No stock movements at all in the previous FY → nothing to pre-fill.
    StockJournalGodownEntry::query()->delete();
    StockJournalEntry::query()->delete();
    StockJournal::query()->delete();
    Voucher::query()->delete();

    $result = VoucherFacade::getPreviousYearClosingStock();

    expect($result['source'])->toBe('running');
    expect($result['closingVoucherNo'])->toBeNull();
    expect($result['closingVoucher'])->toBeNull();
});

// ---------------------------------------------------------------------------
//  Server-side list filters (used by the Opening Stock screen — the unfiltered
//  /vouchers list loads every voucher with all deep relations and exhausts PHP
//  memory on large datasets, so the page passes ?voucherTypeId=<OPNSK id>).
// ---------------------------------------------------------------------------

test('getByVoucherType returns only vouchers of the given type in the user fiscal year', function () {
    \Illuminate\Support\Facades\Cache::flush();

    // sourceVoucher (SALE, fy1) already exists from the setup — add one more
    // SALE voucher in the CURRENT fiscal year (fy2) so the filter has more
    // than one row to work with across years.
    $sale2 = Voucher::create([
        'voucher_no' => 'SALE-0002',
        'voucher_date' => '2026-04-02',
        'voucher_type_id' => $this->voucherTypes['SALE']->id,
        'fiscal_year_id' => $this->fy2->id,
        'company_id' => $this->companyId,
        'module' => 'sales',
        'remarks' => 'Another sale',
        'status' => 'active',
    ]);

    // Entry lists are isolated per fiscal year: only the fy2 voucher is
    // returned (the user works in fy2), not the fy1 sourceVoucher.
    $result = VoucherFacade::getByVoucherType($this->voucherTypes['SALE']->id);

    $ids = $result->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    expect($ids)->toBe([(int) $sale2->id]);

    // An explicit fiscal-year override returns that year's vouchers.
    $fy1Result = VoucherFacade::getByVoucherType($this->voucherTypes['SALE']->id, $this->fy1->id);
    expect($fy1Result->pluck('id')->map(fn ($id) => (int) $id)->all())->toBe([(int) $this->sourceVoucher->id]);

    // A voucher type with no vouchers returns empty (covers the CLSSK filter).
    expect(VoucherFacade::getByVoucherType($this->voucherTypes['CLSSK']->id))->toHaveCount(0);
});

test('getByModule returns only vouchers of the given module', function () {
    \Illuminate\Support\Facades\Cache::flush();

    $opnsk = Voucher::create([
        'voucher_no' => 'OPNSK-0001',
        'voucher_date' => '2026-04-01',
        'voucher_type_id' => $this->voucherTypes['SALE']->id,
        'fiscal_year_id' => $this->fy2->id,
        'company_id' => $this->companyId,
        'module' => 'opening_stock',
        'remarks' => 'Opening stock',
        'status' => 'active',
    ]);

    $result = VoucherFacade::getByModule('opening_stock');

    expect($result->pluck('id')->map(fn ($id) => (int) $id)->all())->toBe([(int) $opnsk->id]);
});
