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
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherType\Models\VoucherType;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * HTTP test for the fiscal_year_open preview endpoint:
 * GET /api/fiscal-years/{newFiscalYear}/open-preview/{previousFiscalYear}
 *
 * The endpoint is protected by the `jwt.cookies` middleware, so the request is
 * authenticated with a real JWT (JWTAuth::fromUser + withToken). Company access
 * is resolved through the user's active UserFiscalYear record, which is seeded
 * here. Assertions lock in the camelCase response shape the frontend
 * `openPreviewSchema` (features/modules/fiscal_year_open/data/schema.ts) expects.
 */
beforeEach(function () {
    $this->companyId = 1;

    // Real user so JWT auth + auth()->id() (used by ScopesCompany) resolve.
    $this->user = User::create([
        'name' => 'Open Preview Test User',
        'email' => 'open-preview-test@example.com',
        'password' => 'password',
    ]);

    // --- Voucher types ---
    $this->category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

    $this->voucherTypes = [];
    foreach (['CLSAC' => 'Closing Account', 'CLSSK' => 'Closing Stock', 'OPNJL' => 'Opening Journal'] as $code => $name) {
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

    // --- Previous fiscal year (closed) with closing vouchers ---
    $this->prevFy = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
        'status' => 'inactive',
        'company_id' => $this->companyId,
        'closed_at' => now(),
    ]);

    // CLSAC: Cash D10,000 / Capital C10,000 (balance sheet carry-forward)
    $this->closingAccountVoucher = Voucher::create([
        'voucher_no' => 'CLSAC-1-20260331',
        'voucher_date' => '2026-03-31',
        'voucher_type_id' => $this->voucherTypes['CLSAC']->id,
        'fiscal_year_id' => $this->prevFy->id,
        'company_id' => $this->companyId,
        'module' => 'system',
        'remarks' => 'Closing account',
        'status' => 'active',
        'is_effecting' => false,
        'effects_account' => true,
        'effects_stock' => false,
    ]);

    VoucherEntry::create(['voucher_id' => $this->closingAccountVoucher->id, 'entry_order' => 1, 'account_ledger_id' => $this->cashLedger->id, 'debit' => 10000, 'credit' => 0, 'remarks' => 'Closing transfer']);
    VoucherEntry::create(['voucher_id' => $this->closingAccountVoucher->id, 'entry_order' => 2, 'account_ledger_id' => $this->capitalLedger->id, 'debit' => 0, 'credit' => 10000, 'remarks' => 'Closing transfer']);

    // CLSSK: 100 KG @ 10 in godown MAIN
    $this->closingStockVoucher = Voucher::create([
        'voucher_no' => 'CLSSK-1-20260331',
        'voucher_date' => '2026-03-31',
        'voucher_type_id' => $this->voucherTypes['CLSSK']->id,
        'fiscal_year_id' => $this->prevFy->id,
        'company_id' => $this->companyId,
        'module' => 'system',
        'remarks' => 'Closing stock',
        'status' => 'active',
        'is_effecting' => false,
        'effects_account' => false,
        'effects_stock' => true,
    ]);

    $closingStockJournal = StockJournal::create([
        'journal_no' => 'CLSSK-1-20260331',
        'journal_date' => '2026-03-31',
        'type' => 'CLOSING',
        'remarks' => 'Closing stock',
    ]);
    $this->closingStockVoucher->update(['stock_journal_id' => $closingStockJournal->id]);

    $closingStockEntry = StockJournalEntry::create([
        'stock_journal_id' => $closingStockJournal->id,
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
        'stock_journal_entry_id' => $closingStockEntry->id,
        'entry_order' => 1,
        'godown_id' => $this->godown->id,
        'actual_quantity' => 100,
        'billing_quantity' => 100,
        'rate' => 10,
        'amount' => 1000,
        'movement_type' => 'in',
    ]);

    // --- New fiscal year (active, same company) ---
    $this->newFy = FiscalYear::create([
        'name' => 'FY 2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    // --- User → fiscal year mapping (ScopesCompany resolves company via this) ---
    UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->prevFy->id,
        'start_date' => $this->prevFy->start_date,
        'end_date' => $this->prevFy->end_date,
    ]);

    // Mint a JWT for the user so the jwt.cookies middleware authenticates.
    $this->token = JWTAuth::fromUser($this->user);
});

// ---------------------------------------------------------------------------
//  preview() — GET /api/fiscal-years/{newFiscalYear}/open-preview/{previousFiscalYear}
// ---------------------------------------------------------------------------

test('preview() returns the opening preview in camelCase matching the frontend schema', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/fiscal-years/'.$this->newFy->id.'/open-preview/'.$this->prevFy->id);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => [
                'previousFiscalYear' => ['id', 'name', 'startDate', 'endDate'],
                'newFiscalYear' => ['id', 'name', 'startDate', 'endDate'],
                'balanceSheetLedgers' => [
                    '*' => ['ledgerId', 'ledgerName', 'nature', 'balance'],
                ],
                'totalLedgers',
                'stockItems' => [
                    '*' => [
                        'itemId',
                        'itemName',
                        'totalQuantity',
                        'godowns' => [
                            '*' => ['godownId', 'godownName', 'quantity'],
                        ],
                    ],
                ],
                'totalStockItems',
            ],
        ])
        // No snake_case keys may leak into the payload.
        ->assertJsonMissingPath('data.previous_fiscal_year')
        ->assertJsonMissingPath('data.new_fiscal_year')
        ->assertJsonMissingPath('data.balance_sheet_ledgers')
        ->assertJsonMissingPath('data.stock_items')
        ->assertJsonMissingPath('data.balanceSheetLedgers.0.ledger_id')
        ->assertJsonMissingPath('data.stockItems.0.item_id')
        ->assertJsonMissingPath('data.stockItems.0.godowns.0.godown_id');

    $data = $response->json('data');

    // Fiscal year refs
    expect($data['previousFiscalYear'])->toMatchArray([
        'id' => $this->prevFy->id,
        'name' => 'FY 2025-26',
        'startDate' => '2025-04-01',
        'endDate' => '2026-03-31',
    ]);

    expect($data['newFiscalYear'])->toMatchArray([
        'id' => $this->newFy->id,
        'name' => 'FY 2026-27',
        'startDate' => '2026-04-01',
        'endDate' => '2027-03-31',
    ]);

    // Balance sheet ledgers: Cash (+10,000), Capital (-10,000)
    expect($data['totalLedgers'])->toBe(2);

    $ledgers = collect($data['balanceSheetLedgers']);
    $cash = $ledgers->firstWhere('ledgerId', $this->cashLedger->id);
    expect($cash['ledgerName'])->toBe('Cash');
    expect($cash['nature'])->toBe('AST');
    expect((float) $cash['balance'])->toBe(10000.0);

    $capital = $ledgers->firstWhere('ledgerId', $this->capitalLedger->id);
    expect($capital['ledgerName'])->toBe('Capital');
    expect($capital['nature'])->toBe('LIA');
    expect((float) $capital['balance'])->toBe(-10000.0);

    // Stock items: Item A, 100 KG in Main Godown
    expect($data['totalStockItems'])->toBe(1);

    $stockItem = $data['stockItems'][0];
    expect($stockItem['itemId'])->toBe($this->item->id);
    expect($stockItem['itemName'])->toBe('Item A');
    expect((float) $stockItem['totalQuantity'])->toBe(100.0);

    $godownEntry = $stockItem['godowns'][0];
    expect($godownEntry['godownId'])->toBe($this->godown->id);
    expect($godownEntry['godownName'])->toBe('Main Godown');
    expect((float) $godownEntry['quantity'])->toBe(100.0);
});

test('preview() returns 404 for a missing new fiscal year', function () {
    $this->withToken($this->token)
        ->getJson('/api/fiscal-years/999999/open-preview/'.$this->prevFy->id)
        ->assertNotFound();
});

test('preview() returns 404 for a missing previous fiscal year', function () {
    $this->withToken($this->token)
        ->getJson('/api/fiscal-years/'.$this->newFy->id.'/open-preview/999999')
        ->assertNotFound();
});

test('preview() fails when the previous fiscal year is not closed', function () {
    // Reopen the previous FY so preview() should reject it.
    $this->prevFy->update(['status' => 'active', 'closed_at' => null]);

    $this->withToken($this->token)
        ->getJson('/api/fiscal-years/'.$this->newFy->id.'/open-preview/'.$this->prevFy->id)
        ->assertStatus(500);
});
