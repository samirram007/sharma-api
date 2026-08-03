<?php

use App\Enums\ActiveInactive;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\AccountGroup\Models\AccountGroup;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountNature\Models\AccountNature;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\FiscalYearClose\Services\FiscalYearCloseService;
use Modules\FiscalYearOpen\Services\FiscalYearOpenService;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournal\Services\StockJournalService;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalEntry\Services\StockJournalEntryService;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockJournalGodownEntry\Services\StockJournalGodownEntryService;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherEntry\Services\VoucherEntryService;
use Modules\VoucherType\Models\VoucherType;

/**
 * End-to-end tests for the fiscal year close/reopen/open workflow.
 *
 * These tests run against the real schema (RefreshDatabase) and drive the real
 * service chain (VoucherEntryService, StockJournalService,
 * StockJournalEntryService, StockJournalGodownEntryService). Only the
 * user→company resolution is mocked, exactly as in FiscalYearCloseTest.
 */
beforeEach(function () {
    $this->companyId = 1;

    // Real user so auth()->id() and the fiscal_years.closed_by FK resolve.
    $this->user = User::create([
        'name' => 'Close Open Test User',
        'email' => 'close-open-test@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    // --- Voucher types (system + one ordinary type for the source voucher) ---
    $this->category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

    $this->voucherTypes = [];
    foreach (['CLSAC' => 'Closing Account', 'CLSSK' => 'Closing Stock', 'OPNJL' => 'Opening Journal', 'SALE' => 'Sales'] as $code => $name) {
        $this->voucherTypes[$code] = VoucherType::create([
            'name' => $name,
            'code' => $code,
            'voucher_category_id' => $this->category->id,
            'is_system' => true,
        ]);
    }

    // --- Chart of accounts ---
    $natures = [];
    foreach (['AST' => 'Assets', 'LIA' => 'Liabilities', 'INC' => 'Income', 'EXP' => 'Expenses', 'EQY' => 'Equity'] as $code => $name) {
        $natures[$code] = AccountNature::create(['name' => $name, 'code' => $code]);
    }

    $assetGroup = AccountGroup::create(['name' => 'Asset Group', 'code' => 'ASTG', 'account_nature_id' => $natures['AST']->id]);
    $incomeGroup = AccountGroup::create(['name' => 'Income Group', 'code' => 'INCG', 'account_nature_id' => $natures['INC']->id]);
    $equityGroup = AccountGroup::create(['name' => 'Equity Group', 'code' => 'EQYG', 'account_nature_id' => $natures['EQY']->id]);

    $this->cashLedger = AccountLedger::create(['name' => 'Cash', 'code' => 'CASH', 'account_group_id' => $assetGroup->id]);
    $this->salesLedger = AccountLedger::create(['name' => 'Sales', 'code' => 'SALES', 'account_group_id' => $incomeGroup->id]);
    $this->capitalLedger = AccountLedger::create(['name' => 'Capital', 'code' => 'CAP', 'account_group_id' => $equityGroup->id]);

    // --- Stock master ---
    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->godown = Godown::create(['name' => 'Main Godown', 'code' => 'MAIN']);
    $this->item = StockItem::create(['name' => 'Item A', 'code' => 'ITEMA', 'stock_unit_id' => $this->unit->id]);

    // --- Previous fiscal year (the one being closed) ---
    $this->fy1 = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    // --- Source business voucher in FY1: Cash D10,000 / Sales C5,000 / Capital C5,000 ---
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

    VoucherEntry::create(['voucher_id' => $this->sourceVoucher->id, 'entry_order' => 1, 'account_ledger_id' => $this->cashLedger->id, 'debit' => 10000, 'credit' => 0, 'remarks' => 'Cash received']);
    VoucherEntry::create(['voucher_id' => $this->sourceVoucher->id, 'entry_order' => 2, 'account_ledger_id' => $this->salesLedger->id, 'debit' => 0, 'credit' => 5000, 'remarks' => 'Sales']);
    VoucherEntry::create(['voucher_id' => $this->sourceVoucher->id, 'entry_order' => 3, 'account_ledger_id' => $this->capitalLedger->id, 'debit' => 0, 'credit' => 5000, 'remarks' => 'Capital contribution']);

    // Source stock movement: 100 KG @ 10 in godown MAIN (feeds closing stock + average rate)
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

    // --- User → company resolution (the only mocked dependency) ---
    $userFiscalYear = Mockery::mock(UserFiscalYear::class)->makePartial();
    $userFiscalYear->fiscal_year = Mockery::mock(FiscalYear::class)->makePartial();
    $userFiscalYear->fiscal_year->company_id = $this->companyId;

    $this->userFiscalYearService = Mockery::mock(UserFiscalYearServiceInterface::class);
    $this->userFiscalYearService->shouldReceive('getByUserId')->andReturn($userFiscalYear);

    // --- Real service chain ---
    $godownEntryService = new StockJournalGodownEntryService;
    $stockJournalEntryService = new StockJournalEntryService($godownEntryService);
    $voucherEntryService = new VoucherEntryService;

    $this->closeService = new FiscalYearCloseService(
        $voucherEntryService,
        new StockJournalService($stockJournalEntryService),
        $stockJournalEntryService,
        $this->userFiscalYearService,
    );

    $this->openService = new FiscalYearOpenService(
        $voucherEntryService,
        new StockJournalService($stockJournalEntryService),
        $stockJournalEntryService,
        $this->userFiscalYearService,
    );
});

afterEach(function () {
    Mockery::close();
});

// ---------------------------------------------------------------------------
//  close() — full transaction path
// ---------------------------------------------------------------------------

test('close() creates closing account + stock vouchers and closes the fiscal year', function () {
    $result = $this->closeService->close($this->fy1->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('closed successfully');

    // CLSAC voucher metadata
    $clsac = Voucher::where('fiscal_year_id', $this->fy1->id)
        ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSAC'))
        ->first();

    expect($clsac)->not->toBeNull();
    expect($clsac->id)->toBe($result['closing_account_voucher_id']);
    expect($clsac->company_id)->toBe($this->companyId);
    expect($clsac->fiscal_year_id)->toBe($this->fy1->id);
    expect($clsac->module)->toBe('system');
    expect($clsac->status)->toBe('active');
    expect($clsac->effects_account)->toBeTrue();
    expect($clsac->effects_stock)->toBeFalse();
    expect($clsac->is_effecting)->toBeFalse();

    // CLSAC entries: Sales D5,000 (P&L transfer) + Capital C5,000 (net profit)
    // + Cash D10,000 (BS carry-forward) + Capital C5,000 (BS carry-forward)
    $entries = $clsac->voucher_entries()->orderBy('entry_order')->get();
    expect($entries)->toHaveCount(4);

    $salesEntry = $entries->firstWhere('account_ledger_id', $this->salesLedger->id);
    expect((float) $salesEntry->debit)->toBe(5000.0);
    expect((float) $salesEntry->credit)->toBe(0.0);
    expect($salesEntry->remarks)->toContain('Closing transfer');

    $cashEntry = $entries->firstWhere('account_ledger_id', $this->cashLedger->id);
    expect((float) $cashEntry->debit)->toBe(10000.0);
    expect((float) $cashEntry->credit)->toBe(0.0);
    expect($cashEntry->remarks)->toContain('carried forward');

    $capitalEntries = $entries->where('account_ledger_id', $this->capitalLedger->id)->values();
    expect($capitalEntries)->toHaveCount(2);
    // One capital entry is the net-profit transfer, the other the BS carry-forward.
    expect($capitalEntries->sum(fn ($e) => (float) $e->credit))->toBe(10000.0);
    expect($capitalEntries->sum(fn ($e) => (float) $e->debit))->toBe(0.0);
    expect($capitalEntries->contains(fn ($e) => str_contains($e->remarks, 'Net profit')))->toBeTrue();
    expect($capitalEntries->contains(fn ($e) => str_contains($e->remarks, 'carried forward')))->toBeTrue();

    // CLSSK voucher metadata
    $clssk = Voucher::where('fiscal_year_id', $this->fy1->id)
        ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSSK'))
        ->first();

    expect($clssk)->not->toBeNull();
    expect($clssk->id)->toBe($result['closing_stock_voucher_id']);
    expect($clssk->company_id)->toBe($this->companyId);
    expect($clssk->module)->toBe('system');
    expect($clssk->effects_stock)->toBeTrue();
    expect($clssk->effects_account)->toBeFalse();
    expect($clssk->stock_journal_id)->not->toBeNull();

    // Closing stock journal: 100 KG @ 10 in godown MAIN
    $closingJournal = $clssk->stock_journal;
    expect($closingJournal->type)->toBe('CLOSING');
    expect($closingJournal->stock_journal_entries)->toHaveCount(1);

    $entry = $closingJournal->stock_journal_entries->first();
    expect($entry->movement_type->value)->toBe('in');
    expect((float) $entry->actual_quantity)->toBe(100.0);
    expect((float) $entry->billing_quantity)->toBe(100.0);
    expect((float) $entry->rate)->toBe(10.0);
    expect((float) $entry->amount)->toBe(1000.0);
    expect($entry->stock_item_id)->toBe($this->item->id);

    expect($entry->stock_journal_godown_entries)->toHaveCount(1);
    $godownEntry = $entry->stock_journal_godown_entries->first();
    expect((float) $godownEntry->actual_quantity)->toBe(100.0);
    expect($godownEntry->godown_id)->toBe($this->godown->id);

    // Fiscal year marked closed
    $this->fy1->refresh();
    expect($this->fy1->status)->toBe(ActiveInactive::Inactive);
    expect($this->fy1->closed_at)->not->toBeNull();
    expect($this->fy1->closed_by)->toBe($this->user->id);
});

test('close() rolls back every change when a closing voucher type is missing', function () {
    VoucherType::where('code', 'CLSSK')->delete();

    try {
        $this->closeService->close($this->fy1->id);
        $this->fail('Expected close() to throw.');
    } catch (ModelNotFoundException) {
        // Expected — CLSSK voucher type is gone.
    }

    // Nothing from the failed close was persisted: only the source voucher,
    // its 3 entries and its stock journal remain.
    expect(Voucher::count())->toBe(1);
    expect(VoucherEntry::count())->toBe(3);
    expect(StockJournal::count())->toBe(1);
    expect(StockJournalEntry::count())->toBe(1);
    expect(StockJournalGodownEntry::count())->toBe(1);

    $this->fy1->refresh();
    expect($this->fy1->status)->toBe(ActiveInactive::Active);
    expect($this->fy1->closed_at)->toBeNull();
});

// ---------------------------------------------------------------------------
//  reopen() — full transaction path
// ---------------------------------------------------------------------------

test('reopen() deletes closing vouchers and restores the fiscal year', function () {
    $this->closeService->close($this->fy1->id);

    $result = $this->closeService->reopen($this->fy1->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('reopened');

    // Closing vouchers + their entries and stock journals are gone;
    // the original source voucher is untouched.
    expect(Voucher::count())->toBe(1);
    expect(VoucherEntry::count())->toBe(3);
    expect(StockJournal::count())->toBe(1);
    expect(StockJournalEntry::count())->toBe(1);
    expect(StockJournalGodownEntry::count())->toBe(1);

    $this->fy1->refresh();
    expect($this->fy1->status)->toBe(ActiveInactive::Active);
    expect($this->fy1->closed_at)->toBeNull();
    expect($this->fy1->closed_by)->toBeNull();
});

test('reopen() throws when the fiscal year is not closed', function () {
    $this->closeService->reopen($this->fy1->id);
})->throws(Exception::class, 'is not closed');

// ---------------------------------------------------------------------------
//  open() — full transaction path
// ---------------------------------------------------------------------------

test('open() carries forward balances and stock into the new fiscal year', function () {
    // Close the previous FY through the real close() path first.
    $this->closeService->close($this->fy1->id);

    $fy2 = FiscalYear::create([
        'name' => 'FY 2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    // The acting user is currently mapped to the previous FY (company 1).
    UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fy1->id,
        'start_date' => $this->fy1->start_date,
        'end_date' => $this->fy1->end_date,
    ]);

    $result = $this->openService->open($fy2->id, $this->fy1->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('opened successfully');
    expect($result['newFiscalYearId'])->toBe($fy2->id);

    // OPNJL voucher metadata
    $opn = Voucher::where('fiscal_year_id', $fy2->id)
        ->whereHas('voucher_type', fn ($q) => $q->where('code', 'OPNJL'))
        ->first();

    expect($opn)->not->toBeNull();
    expect($opn->id)->toBe($result['openingJournalVoucherId']);
    expect($opn->company_id)->toBe($this->companyId);
    expect($opn->module)->toBe('system');
    expect($opn->is_effecting)->toBeTrue();
    expect($opn->effects_account)->toBeTrue();
    expect($opn->effects_stock)->toBeTrue();

    // Opening entries: Cash D10,000 + Capital C5,000 (profit) + Capital C5,000 (BS)
    $opnEntries = $opn->voucher_entries()->orderBy('entry_order')->get();
    expect($opnEntries)->toHaveCount(3);
    expect($opnEntries->sum(fn ($e) => (float) $e->debit))->toBe(10000.0);
    expect($opnEntries->sum(fn ($e) => (float) $e->credit))->toBe(10000.0);

    $cashOpen = $opnEntries->firstWhere('account_ledger_id', $this->cashLedger->id);
    expect((float) $cashOpen->debit)->toBe(10000.0);
    expect((float) $cashOpen->credit)->toBe(0.0);

    $capitalOpens = $opnEntries->where('account_ledger_id', $this->capitalLedger->id)->values();
    expect($capitalOpens)->toHaveCount(2);
    expect($capitalOpens->sum(fn ($e) => (float) $e->credit))->toBe(10000.0);

    // Opening stock journal carries the 100 KG @ 10 forward
    $opn->refresh();
    expect($opn->stock_journal_id)->not->toBeNull();

    $opnJournal = $opn->stock_journal;
    expect($opnJournal->type)->toBe('OPENING');
    expect($opnJournal->stock_journal_entries)->toHaveCount(1);

    $openEntry = $opnJournal->stock_journal_entries->first();
    expect($openEntry->movement_type->value)->toBe('in');
    expect((float) $openEntry->actual_quantity)->toBe(100.0);
    expect((float) $openEntry->rate)->toBe(10.0);
    expect((float) $openEntry->amount)->toBe(1000.0);

    expect($openEntry->stock_journal_godown_entries)->toHaveCount(1);
    $openGodown = $openEntry->stock_journal_godown_entries->first();
    expect((float) $openGodown->actual_quantity)->toBe(100.0);
    expect($openGodown->godown_id)->toBe($this->godown->id);

    // The user's fiscal year mapping moved to the new FY for this company.
    $userFy = UserFiscalYear::where('user_id', (string) $this->user->id)->first();
    expect($userFy->fiscal_year_id)->toBe((string) $fy2->id);
    expect($userFy->start_date->format('Y-m-d'))->toBe('2026-04-01');
    expect($userFy->end_date->format('Y-m-d'))->toBe('2027-03-31');
});

test('open() throws when the previous fiscal year is not closed', function () {
    $fy2 = FiscalYear::create([
        'name' => 'FY 2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    $this->openService->open($fy2->id, $this->fy1->id);
})->throws(Exception::class, 'must be closed');
