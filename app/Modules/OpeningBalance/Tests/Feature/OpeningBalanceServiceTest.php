<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\OpeningBalance\Facades\OpeningBalanceFacade;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherType\Models\VoucherType;

// ---------------------------------------------------------------------------
// OpeningBalanceFacade → OpeningBalanceServiceInterface → OpeningBalanceService
// ---------------------------------------------------------------------------
//
// The OpeningBalanceService has constructor dependencies:
//   - UserFiscalYearServiceInterface
//   - VoucherEntryServiceInterface
//   - StockJournalServiceInterface
//   - StockJournalEntryServiceInterface
//
// Most methods (getSetupData, store) require complex DB setup (AccountLedgers,
// AccountNatures, StockItems, Godowns, etc.). For facade delegation tests,
// we focus on the simplest method: getStatus().
//
// getStatus() needs: a User with an active UserFiscalYear and a FY.
// It returns info about whether an OPNJL voucher exists.

uses(RefreshDatabase::class)->group('opening-balance-facade');

describe('OpeningBalanceFacade delegation', function () {
    it('resolves facade and calls getStatus with no opening', function () {
        // Arrange: minimal setup — FiscalYear + User + UserFiscalYear
        $fiscalYear = FiscalYear::create([
            'name' => 'FY 2026-27',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
        ]);

        $user = User::factory()->create();
        UserFiscalYear::create([
            'user_id' => $user->id,
            'fiscal_year_id' => $fiscalYear->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);
        $this->actingAs($user);

        // Act
        $status = OpeningBalanceFacade::getStatus();

        // Assert
        expect($status)
            ->toBeArray()
            ->toHaveKey('has_existing_opening')
            ->toHaveKey('opening_voucher_id')
            ->toHaveKey('voucher_no')
            ->toHaveKey('fiscal_year');

        expect($status['has_existing_opening'])->toBeFalse();
        expect($status['fiscal_year']['name'])->toBe('FY 2026-27');
    });

    it('getStatus detects existing opening balance', function () {
        // Arrange: create UserFiscalYear setup
        $fiscalYear = FiscalYear::create([
            'name' => 'FY 2026-27',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
        ]);

        $user = User::factory()->create();
        UserFiscalYear::create([
            'user_id' => $user->id,
            'fiscal_year_id' => $fiscalYear->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);
        $this->actingAs($user);

        // Create an OPNJL voucher so getStatus detects it
        $voucherType = VoucherType::create([
            'name' => 'Opening Journal',
            'code' => 'OPNJL',
            'status' => 'active',
        ]);

        Voucher::create([
            'voucher_no' => 'OPNJL-'.$fiscalYear->id.'-test',
            'voucher_date' => '2026-04-01',
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => $fiscalYear->id,
            'remarks' => 'Test opening',
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'system',
        ]);

        // Act
        $status = OpeningBalanceFacade::getStatus();

        // Assert
        expect($status['has_existing_opening'])->toBeTrue();
        expect($status['opening_voucher_id'])->not->toBeNull();
    });
});

describe('OpeningBalanceService edge cases', function () {
    it('throws exception when getStatus is called without authenticated user', function () {
        // No actingAs → Auth::id() returns null
        expect(fn () => OpeningBalanceFacade::getStatus())
            ->toThrow(Exception::class);
    });

    it('throws exception when user has no UserFiscalYear set', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        // User exists but has no UserFiscalYear → service will fail
        expect(fn () => OpeningBalanceFacade::getStatus())
            ->toThrow(Exception::class, 'UserFiscalYear not set');
    });
});

// @todo: Add facade tests for getSetupData() and store() – these require
// significantly more DB setup (AccountNatures, AccountGroups, AccountLedgers,
// StockItems, Godowns, etc.) and are already tested via the API endpoint
// tests in OpeningBalanceTest.php.
