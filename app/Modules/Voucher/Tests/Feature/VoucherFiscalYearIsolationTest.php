<?php

use Illuminate\Support\Facades\Cache;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Facades\VoucherFacade;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * All reports AND entries must be totally isolated per fiscal year. These
 * tests lock in the rule that voucher list queries (which power every entry
 * list on the transaction screens) only return vouchers of the user's
 * assigned fiscal year — never other years' data.
 */
beforeEach(function () {
    $this->companyId = 1;

    $this->user = User::create([
        'name' => 'FY Isolation Test User',
        'email' => 'fy-isolation@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    $category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

    $this->voucherType = VoucherType::create([
        'name' => 'Sales',
        'code' => 'SALE',
        'voucher_category_id' => $category->id,
        'is_system' => true,
    ]);

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

    // The acting user works in FY2.
    UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fy2->id,
        'start_date' => $this->fy2->start_date,
        'end_date' => $this->fy2->end_date,
    ]);

    $this->fy1Voucher = Voucher::create([
        'voucher_no' => 'SALE-0001',
        'voucher_date' => '2025-06-15',
        'voucher_type_id' => $this->voucherType->id,
        'fiscal_year_id' => $this->fy1->id,
        'company_id' => $this->companyId,
        'module' => 'sales',
        'status' => 'active',
    ]);

    $this->fy2Voucher = Voucher::create([
        'voucher_no' => 'SALE-0002',
        'voucher_date' => '2026-06-15',
        'voucher_type_id' => $this->voucherType->id,
        'fiscal_year_id' => $this->fy2->id,
        'company_id' => $this->companyId,
        'module' => 'sales',
        'status' => 'active',
    ]);
});

test('getAll() only returns vouchers of the user assigned fiscal year', function () {
    Cache::flush();

    $vouchers = VoucherFacade::getAll();

    $ids = $vouchers->pluck('id')->map(fn ($id) => (int) $id)->all();
    expect($ids)->toBe([(int) $this->fy2Voucher->id])
        ->and($ids)->not->toContain((int) $this->fy1Voucher->id);
});

test('getAll() can be overridden to a specific fiscal year', function () {
    Cache::flush();

    $vouchers = VoucherFacade::getAll($this->fy1->id);

    $ids = $vouchers->pluck('id')->map(fn ($id) => (int) $id)->all();
    expect($ids)->toBe([(int) $this->fy1Voucher->id]);
});

test('getByModule() only returns vouchers of the user assigned fiscal year', function () {
    Cache::flush();

    $vouchers = VoucherFacade::getByModule('sales');

    $ids = $vouchers->pluck('id')->map(fn ($id) => (int) $id)->all();
    expect($ids)->toBe([(int) $this->fy2Voucher->id])
        ->and($ids)->not->toContain((int) $this->fy1Voucher->id);
});

test('getByModule() can be overridden to a specific fiscal year', function () {
    Cache::flush();

    $vouchers = VoucherFacade::getByModule('sales', $this->fy1->id);

    $ids = $vouchers->pluck('id')->map(fn ($id) => (int) $id)->all();
    expect($ids)->toBe([(int) $this->fy1Voucher->id]);
});

test('GET /api/vouchers only returns the user fiscal year entries', function () {
    Cache::flush();
    $token = JWTAuth::fromUser($this->user);

    $response = $this->withToken($token)->getJson('/api/vouchers');

    $response->assertOk()->assertJsonPath('success', true);
    $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toBe([(int) $this->fy2Voucher->id])
        ->and($ids)->not->toContain((int) $this->fy1Voucher->id);
});

test('GET /api/vouchers respects an explicit fiscal_year_id query param', function () {
    Cache::flush();
    $token = JWTAuth::fromUser($this->user);

    $response = $this->withToken($token)
        ->getJson('/api/vouchers?fiscal_year_id='.$this->fy1->id);

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toBe([(int) $this->fy1Voucher->id]);
});
