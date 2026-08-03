<?php

use Modules\AccountGroup\Models\AccountGroup;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountNature\Models\AccountNature;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherType\Models\VoucherType;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * HTTP tests for OpeningEntryReportController (show + groupedByLedger).
 *
 * These endpoints are protected by the `jwt.cookies` middleware, so each
 * request authenticates with a real JWT issued via JWTAuth::fromUser().
 * The assertions lock in the camelCase response shape the frontend zod
 * schemas (opening_entry_report / fiscal_year_open) expect.
 */
beforeEach(function () {
    $this->companyId = 1;

    // Real user so the JWT can be minted and resolved by the middleware.
    $this->user = User::create([
        'name' => 'Opening Report Test User',
        'email' => 'opening-report-test@example.com',
        'password' => 'password',
    ]);

    // --- Voucher types (OPNJL is what the report filters on) ---
    $this->category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

    $this->voucherTypes = [];
    foreach (['OPNJL' => 'Opening Journal', 'CLSAC' => 'Closing Account'] as $code => $name) {
        $this->voucherTypes[$code] = VoucherType::create([
            'name' => $name,
            'code' => $code,
            'voucher_category_id' => $this->category->id,
            'is_system' => true,
        ]);
    }

    // --- Chart of accounts ---
    $natures = [];
    foreach (['AST' => 'Assets', 'LIA' => 'Liabilities', 'EQY' => 'Equity'] as $code => $name) {
        $natures[$code] = AccountNature::create(['name' => $name, 'code' => $code]);
    }

    $assetGroup = AccountGroup::create(['name' => 'Asset Group', 'code' => 'ASTG', 'account_nature_id' => $natures['AST']->id]);
    $liabilityGroup = AccountGroup::create(['name' => 'Liability Group', 'code' => 'LIAG', 'account_nature_id' => $natures['LIA']->id]);

    $this->cashLedger = AccountLedger::create(['name' => 'Cash', 'code' => 'CASH', 'account_group_id' => $assetGroup->id]);
    $this->capitalLedger = AccountLedger::create(['name' => 'Capital', 'code' => 'CAP', 'account_group_id' => $liabilityGroup->id]);

    // --- Stock master ---
    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->godown = Godown::create(['name' => 'Main Godown', 'code' => 'MAIN']);
    $this->item = StockItem::create(['name' => 'Item A', 'code' => 'ITEMA', 'stock_unit_id' => $this->unit->id]);

    // --- Fiscal year being reported on ---
    $this->fy = FiscalYear::create([
        'name' => 'FY 2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    // --- Opening journal voucher: Cash D10,000 / Capital C10,000 + stock 100 KG ---
    $this->openingVoucher = Voucher::create([
        'voucher_no' => 'OPNJL-1-20260401',
        'voucher_date' => '2026-04-01',
        'voucher_type_id' => $this->voucherTypes['OPNJL']->id,
        'fiscal_year_id' => $this->fy->id,
        'company_id' => $this->companyId,
        'module' => 'system',
        'remarks' => 'Unified opening entry',
        'status' => 'active',
        'is_effecting' => true,
        'effects_account' => true,
        'effects_stock' => true,
    ]);

    VoucherEntry::create(['voucher_id' => $this->openingVoucher->id, 'entry_order' => 1, 'account_ledger_id' => $this->cashLedger->id, 'debit' => 10000, 'credit' => 0, 'remarks' => 'Opening balance']);
    VoucherEntry::create(['voucher_id' => $this->openingVoucher->id, 'entry_order' => 2, 'account_ledger_id' => $this->capitalLedger->id, 'debit' => 0, 'credit' => 10000, 'remarks' => 'Opening balance']);

    $this->openingStockJournal = StockJournal::create([
        'journal_no' => 'OPNJL-1-20260401',
        'journal_date' => '2026-04-01',
        'type' => 'OPENING',
        'remarks' => 'Opening stock',
    ]);
    $this->openingVoucher->update(['stock_journal_id' => $this->openingStockJournal->id]);

    $this->openingStockEntry = StockJournalEntry::create([
        'stock_journal_id' => $this->openingStockJournal->id,
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
        'stock_journal_entry_id' => $this->openingStockEntry->id,
        'entry_order' => 1,
        'godown_id' => $this->godown->id,
        'actual_quantity' => 100,
        'billing_quantity' => 100,
        'rate' => 10,
        'amount' => 1000,
        'movement_type' => 'in',
    ]);

    // Mint a JWT for the user so the jwt.cookies middleware authenticates.
    $this->token = JWTAuth::fromUser($this->user);
});

// ---------------------------------------------------------------------------
//  show() — GET /api/fiscal-years/{id}/opening-entry-report
// ---------------------------------------------------------------------------

test('show() returns the opening entry report in camelCase', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/fiscal-years/'.$this->fy->id.'/opening-entry-report');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => [
                'fiscalYear' => ['id', 'name', 'startDate', 'endDate'],
                'vouchers' => [
                    '*' => [
                        'id',
                        'voucherNo',
                        'voucherDate',
                        'remarks',
                        'createdAt',
                        'voucherEntries' => [
                            '*' => [
                                'id',
                                'entryOrder',
                                'accountLedgerId',
                                'accountLedgerName',
                                'nature',
                                'natureCode',
                                'debit',
                                'credit',
                                'remarks',
                            ],
                        ],
                        'totalDebit',
                        'totalCredit',
                        'stockJournal' => [
                            'id',
                            'journalNo',
                            'journalDate',
                            'type',
                            'entries' => [
                                '*' => [
                                    'id',
                                    'entryOrder',
                                    'stockItemId',
                                    'stockItemName',
                                    'stockUnitId',
                                    'stockUnitName',
                                    'actualQuantity',
                                    'rate',
                                    'amount',
                                    'godownEntries' => [
                                        '*' => [
                                            'id',
                                            'entryOrder',
                                            'godownId',
                                            'godownName',
                                            'actualQuantity',
                                            'remarks',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'totalVouchers',
            ],
        ])
        // No snake_case keys may leak into the payload.
        ->assertJsonMissingPath('data.fiscal_year')
        ->assertJsonMissingPath('data.vouchers.0.voucher_entries')
        ->assertJsonMissingPath('data.vouchers.0.stock_journal');

    $data = $response->json('data');

    expect($data['fiscalYear'])->toMatchArray([
        'id' => $this->fy->id,
        'name' => 'FY 2026-27',
        'startDate' => '2026-04-01',
        'endDate' => '2027-03-31',
    ]);

    expect($data['totalVouchers'])->toBe(1);

    $voucher = $data['vouchers'][0];
    expect($voucher['voucherNo'])->toBe('OPNJL-1-20260401');
    // JSON encoding may drop the trailing .0 on whole-number floats — compare as floats.
    expect((float) $voucher['totalDebit'])->toBe(10000.0);
    expect((float) $voucher['totalCredit'])->toBe(10000.0);

    $entries = $voucher['voucherEntries'];
    expect($entries)->toHaveCount(2);

    $cashEntry = collect($entries)->firstWhere('accountLedgerId', $this->cashLedger->id);
    expect($cashEntry['accountLedgerName'])->toBe('Cash');
    expect($cashEntry['nature'])->toBe('Assets');
    expect($cashEntry['natureCode'])->toBe('AST');
    expect((float) $cashEntry['debit'])->toBe(10000.0);
    expect((float) $cashEntry['credit'])->toBe(0.0);

    $capitalEntry = collect($entries)->firstWhere('accountLedgerId', $this->capitalLedger->id);
    expect($capitalEntry['accountLedgerName'])->toBe('Capital');
    expect($capitalEntry['nature'])->toBe('Liabilities');
    expect($capitalEntry['natureCode'])->toBe('LIA');
    expect((float) $capitalEntry['debit'])->toBe(0.0);
    expect((float) $capitalEntry['credit'])->toBe(10000.0);

    $stock = $voucher['stockJournal'];
    expect($stock['journalNo'])->toBe('OPNJL-1-20260401');
    expect($stock['type'])->toBe('OPENING');
    expect($stock['entries'])->toHaveCount(1);

    $stockEntry = $stock['entries'][0];
    expect($stockEntry['stockItemName'])->toBe('Item A');
    expect((float) $stockEntry['actualQuantity'])->toBe(100.0);
    expect((float) $stockEntry['rate'])->toBe(10.0);
    expect((float) $stockEntry['amount'])->toBe(1000.0);

    $godownEntry = $stockEntry['godownEntries'][0];
    expect($godownEntry['godownName'])->toBe('Main Godown');
    expect((float) $godownEntry['actualQuantity'])->toBe(100.0);
});

test('show() returns 404 for a missing fiscal year', function () {
    $this->withToken($this->token)
        ->getJson('/api/fiscal-years/999999/opening-entry-report')
        ->assertNotFound();
});

test('show() returns an empty payload when no OPNJL voucher type exists', function () {
    VoucherType::where('code', 'OPNJL')->delete();

    $this->withToken($this->token)
        ->getJson('/api/fiscal-years/'.$this->fy->id.'/opening-entry-report')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', []);
});

// ---------------------------------------------------------------------------
//  groupedByLedger() — GET /api/fiscal-years/{id}/opening-entry-report/grouped-by-ledger
// ---------------------------------------------------------------------------

test('groupedByLedger() returns camelCase ledger grouping', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/fiscal-years/'.$this->fy->id.'/opening-entry-report/grouped-by-ledger');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => [
                '*' => ['ledgerId', 'ledgerName', 'voucherCount', 'totalDebit', 'totalCredit', 'netBalance'],
            ],
        ])
        ->assertJsonMissingPath('data.0.ledger_id')
        ->assertJsonMissingPath('data.0.voucher_count');

    $grouped = collect($response->json('data'));

    // Cash: 1 voucher, D10,000, net +10,000 — ordered by net_balance desc first.
    $cash = $grouped->firstWhere('ledgerId', $this->cashLedger->id);
    expect($cash['ledgerName'])->toBe('Cash');
    expect($cash['voucherCount'])->toBe(1);
    // JSON encoding may drop the trailing .0 on whole-number floats — compare as floats.
    expect((float) $cash['totalDebit'])->toBe(10000.0);
    expect((float) $cash['totalCredit'])->toBe(0.0);
    expect((float) $cash['netBalance'])->toBe(10000.0);

    $capital = $grouped->firstWhere('ledgerId', $this->capitalLedger->id);
    expect($capital['ledgerName'])->toBe('Capital');
    expect($capital['voucherCount'])->toBe(1);
    expect((float) $capital['totalDebit'])->toBe(0.0);
    expect((float) $capital['totalCredit'])->toBe(10000.0);
    expect((float) $capital['netBalance'])->toBe(-10000.0);
});

test('groupedByLedger() returns 404 for a missing fiscal year', function () {
    $this->withToken($this->token)
        ->getJson('/api/fiscal-years/999999/opening-entry-report/grouped-by-ledger')
        ->assertNotFound();
});
