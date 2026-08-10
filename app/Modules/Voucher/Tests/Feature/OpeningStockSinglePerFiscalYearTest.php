<?php

use Illuminate\Validation\ValidationException;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Facades\VoucherFacade;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;

/**
 * Tests for the server-side "only ONE opening stock (OPNSK) voucher per
 * fiscal year" rule enforced by VoucherService.
 *
 * The frontend also blocks duplicate opening stock vouchers, but the backend
 * must reject them too — any second client (or a stale tab) hitting store
 * with a second OPNSK voucher for the same fiscal year must fail.
 */
beforeEach(function () {
    $this->companyId = 1;

    $this->user = User::create([
        'name' => 'Opening Stock Guard Test User',
        'email' => 'opnsk-guard@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    $category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

    $this->opnskType = VoucherType::create([
        'name' => 'Opening Stock',
        'code' => 'OPNSK',
        'voucher_category_id' => $category->id,
        'is_system' => true,
    ]);

    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);

    $this->fy = FiscalYear::create([
        'name' => 'FY 2026-27',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'status' => 'active',
        'company_id' => $this->companyId,
    ]);

    UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fy->id,
        'start_date' => $this->fy->start_date,
        'end_date' => $this->fy->end_date,
    ]);
});

test('store rejects a second opening stock voucher for the same fiscal year', function () {
    // First opening stock voucher — allowed.
    Voucher::create([
        'voucher_no' => 'OPNSK-0001',
        'voucher_date' => '2026-04-01',
        'voucher_type_id' => $this->opnskType->id,
        'fiscal_year_id' => $this->fy->id,
        'company_id' => $this->companyId,
        'module' => 'opening_stock',
        'status' => 'active',
    ]);

    $duplicate = [
        'module' => 'opening_stock',
        'voucher_type_id' => $this->opnskType->id,
        'fiscal_year_id' => $this->fy->id,
        'voucher_date' => '2026-04-01',
        'status' => 'active',
        'stock_journal' => null,
        'voucher_entries' => [],
    ];

    VoucherFacade::store($duplicate);
})->throws(
    ValidationException::class,
    'Opening stock already exists for this fiscal year. Only one opening stock voucher is allowed per fiscal year.'
);

test('store allows an opening stock voucher when none exists yet', function () {
    $voucher = VoucherFacade::store([
        'module' => 'opening_stock',
        'voucher_type_id' => $this->opnskType->id,
        'fiscal_year_id' => $this->fy->id,
        'voucher_date' => '2026-04-01',
        'status' => 'active',
        'stock_journal' => null,
        'voucher_entries' => [],
    ]);

    expect($voucher->id)->not->toBeNull();
    expect($voucher->voucher_type_id)->toBe($this->opnskType->id);
});
