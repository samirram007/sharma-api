<?php

use Illuminate\Support\Facades\Cache;
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
use Modules\VoucherType\Models\VoucherType;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * End-to-end HTTP test of the Opening Stock save flow — the exact sequence the
 * frontend performs:
 *
 *  1. GET  /vouchers/opening-stock/voucher-type        (resolve OPNSK id)
 *  2. GET  /vouchers/opening-stock/previous-year-closing (Fetch Previous Year Closing Stock)
 *  3. POST /vouchers                                   (save the opening stock voucher)
 *  4. GET  /vouchers/{id}                              (reload the saved voucher)
 *
 * These routes are protected by `jwt.cookies`, so every request authenticates
 * with a real JWT minted via JWTAuth::fromUser().
 */
beforeEach(function () {
    $this->companyId = 1;

    $this->user = User::create([
        'name' => 'Opening Stock E2E Test User',
        'email' => 'opnsk-e2e@example.com',
        'password' => 'password',
    ]);

    $category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

    $this->voucherTypes = [];
    foreach (['OPNSK' => 'Opening Stock', 'SALE' => 'Sales'] as $code => $name) {
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

    // --- Previous FY (2025-26) with stock movement + current FY (2026-27) ---
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

    // --- Source stock movement in the PREVIOUS FY: 100 KG @ 10 ---
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

    // Mint a JWT so the jwt.cookies middleware authenticates the requests.
    $this->token = JWTAuth::fromUser($this->user);
});

test('opening stock voucher saves end-to-end and loads back with its entries', function () {
    // (1) Resolve the OPNSK voucher type id (the runtime lookup the frontend uses).
    $typeResponse = $this->withToken($this->token)
        ->getJson('/api/vouchers/opening-stock/voucher-type');

    $typeResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.code', 'OPNSK');

    $opnskTypeId = $typeResponse->json('data.id');

    // (2) Fetch the previous year closing stock (the "Fetch Previous Year
    // Closing Stock" button) — falls back to the running balance.
    $closingResponse = $this->withToken($this->token)
        ->getJson('/api/vouchers/opening-stock/previous-year-closing');

    $closingResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.previousFiscalYear.id', $this->fy1->id)
        ->assertJsonPath('data.source', 'running');

    $closingVoucher = $closingResponse->json('data.closingVoucher');
    expect($closingVoucher['stockJournal']['stockJournalEntries'])->toHaveCount(1);
    expect((float) $closingVoucher['stockJournal']['stockJournalEntries'][0]['actualQuantity'])->toBe(100.0);

    // (3) Save the opening stock voucher with the EXACT payload the frontend
    // sends: camelCase keys, normalized to snake_case by the
    // NormalizeRequestKeys middleware. Deliberately sends a non-FY-start date
    // (2026-04-15) — the backend must force it back to the FY start date
    // (2026-04-01), proving the date-lock rule.
    $payload = [
        'voucherTypeId' => $opnskTypeId,
        'fiscalYearId' => $this->fy2->id,
        'voucherDate' => '2026-04-15',
        'module' => 'opening_stock',
        'status' => 'active',
        'remarks' => 'Opening stock E2E',
        'stockJournal' => [
            'journalNo' => '',
            'journalDate' => '2026-04-01',
            'type' => 'in',
            'remarks' => 'Opening stock',
            'stockJournalEntries' => [
                [
                    'stockItemId' => $this->item->id,
                    'stockUnitId' => $this->unit->id,
                    'itemCost' => 0,
                    'actualQuantity' => 100,
                    'billingQuantity' => 100,
                    'rate' => 10,
                    'rateUnitId' => $this->unit->id,
                    'unitRatio' => 1,
                    'discountPercentage' => 0,
                    'discount' => 0,
                    'amount' => 1000,
                    'movementType' => 'in',
                    'stockJournalGodownEntries' => [
                        [
                            'godownId' => $this->godown->id,
                            'actualQuantity' => 100,
                            'billingQuantity' => 100,
                            'rate' => 10,
                            'amount' => 1000,
                            'movementType' => 'in',
                        ],
                    ],
                ],
            ],
        ],
        'voucherEntries' => [],
    ];

    $saveResponse = $this->withToken($this->token)
        ->postJson('/api/vouchers', $payload);

    $saveResponse->assertStatus(201)
        ->assertJsonPath('success', true);

    $saved = $saveResponse->json('data');
    // Voucher numbers use the 4-char code prefix (substr('OPNSK', 0, 4) = 'OPNS').
    expect($saved['voucherNo'])->toContain('OPNS-');
    // The backend stamps the OPNSK type id + the fiscal-year-start date
    // (the client sent 2026-04-15 — it must be forced back to 2026-04-01).
    expect($saved['voucherTypeId'])->toBe($opnskTypeId);
    expect($saved['module'])->toBe('opening_stock');
    expect($saved['fiscalYearId'])->toBe($this->fy2->id);
    // The date-lock rule: the client sent 2026-04-15 but the backend must
    // force the voucher date to the fiscal-year start (2026-04-01). The
    // response serializes it as a UTC ISO timestamp (app timezone shift),
    // so parse back in the app timezone before comparing the date.
    expect(\Carbon\Carbon::parse($saved['voucherDate'])
        ->setTimezone(config('app.timezone'))->format('Y-m-d'))
        ->toBe('2026-04-01');

    // The whole nested graph persisted to the database.
    // The date-lock is already proven by the response assertion above — the
    // DB column comparison is skipped because the date cast formats
    // differently per driver (SQLite vs MariaDB).
    $this->assertDatabaseHas('vouchers', [
        'id' => $saved['id'],
        'voucher_type_id' => $opnskTypeId,
        'fiscal_year_id' => $this->fy2->id,
        'module' => 'opening_stock',
    ]);
    $this->assertDatabaseHas('stock_journals', ['id' => $saved['stockJournalId']]);
    $this->assertDatabaseHas('stock_journal_entries', [
        'stock_journal_id' => $saved['stockJournalId'],
        'stock_item_id' => $this->item->id,
        'actual_quantity' => 100,
        'amount' => 1000,
    ]);
    $persistedEntryId = StockJournalEntry::where('stock_journal_id', $saved['stockJournalId'])
        ->where('stock_item_id', $this->item->id)
        ->value('id');
    $this->assertDatabaseHas('stock_journal_godown_entries', [
        'stock_journal_entry_id' => $persistedEntryId,
        'godown_id' => $this->godown->id,
        'actual_quantity' => 100,
        'amount' => 1000,
    ]);

    // (4) Reload the saved voucher (the auto-load when opening the page again)
    // — the entries must come back exactly as saved.
    $reloadResponse = $this->withToken($this->token)
        ->getJson('/api/vouchers/'.$saved['id']);

    $reloadResponse->assertOk()
        ->assertJsonPath('success', true);

    $reloaded = $reloadResponse->json('data');
    expect($reloaded['voucherNo'])->toBe($saved['voucherNo']);
    expect($reloaded['stockJournal']['stockJournalEntries'])->toHaveCount(1);

    $entry = $reloaded['stockJournal']['stockJournalEntries'][0];
    expect($entry['stockItemId'])->toBe($this->item->id);
    expect((float) $entry['actualQuantity'])->toBe(100.0);
    expect((float) $entry['amount'])->toBe(1000.0);
    expect($entry['stockJournalGodownEntries'])->toHaveCount(1);
    expect($entry['stockJournalGodownEntries'][0]['godownId'])->toBe($this->godown->id);
});

test('a second opening stock voucher for the same fiscal year is rejected with 422', function () {
    $opnskTypeId = $this->voucherTypes['OPNSK']->id;

    $payload = [
        'voucher_type_id' => $opnskTypeId,
        'fiscal_year_id' => $this->fy2->id,
        'voucher_date' => '2026-04-01',
        'module' => 'opening_stock',
        'status' => 'active',
        'stock_journal' => null,
        'voucher_entries' => [],
    ];

    // First save succeeds.
    $this->withToken($this->token)->postJson('/api/vouchers', $payload)
        ->assertStatus(201);

    // Second save for the same fiscal year is rejected by the backend guard.
    $this->withToken($this->token)->postJson('/api/vouchers', $payload)
        ->assertStatus(422)
        ->assertJsonPath('message', 'Opening stock already exists for this fiscal year. Only one opening stock voucher is allowed per fiscal year.');

    // The rejected request must not leave a partial row behind — exactly one
    // OPNSK voucher still exists.
    expect(Voucher::where('voucher_type_id', $opnskTypeId)->count())->toBe(1);
});
