<?php

namespace Modules\DayBook\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccountGroup\Models\AccountGroup;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountNature\Models\AccountNature;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherType\Models\VoucherType;
use Tests\TestCase;

class DayBookServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private FiscalYear $fiscalYear;

    private VoucherType $voucherType;

    private AccountNature $assetNature;

    private AccountGroup $assetGroup;

    protected function setUp(): void
    {
        parent::setUp();

        // Fiscal year
        $this->fiscalYear = FiscalYear::create([
            'name' => 'FY 2025-26',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);

        // User with UserFiscalYear
        $this->user = User::factory()->create();
        UserFiscalYear::create([
            'user_id' => $this->user->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'current_date' => '2025-04-01',
        ]);
        $this->actingAs($this->user);

        // Voucher type
        $this->voucherType = VoucherType::create([
            'name' => 'Payment',
            'code' => 'PAYM',
            'status' => 'active',
        ]);

        // Account nature & group for ledger entries
        $this->assetNature = AccountNature::create(['name' => 'Asset', 'code' => 'AST', 'accounting_effect' => 'debit']);
        $this->assetGroup = AccountGroup::create(['name' => 'Current Assets', 'account_nature_id' => $this->assetNature->id, 'code' => 'CA']);
    }

    /**
     * Create a voucher with a single ledger entry.
     */
    private function createVoucherWithEntry(array $overrides = []): Voucher
    {
        $ledger = AccountLedger::create([
            'name' => 'Cash Account',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);

        $voucher = Voucher::create(array_merge([
            'voucher_no' => 'PAYM-1',
            'voucher_date' => '2025-04-15',
            'voucher_type_id' => $this->voucherType->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Test payment voucher',
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'accounts',
        ], $overrides));

        VoucherEntry::create([
            'voucher_id' => $voucher->id,
            'entry_order' => 1,
            'account_ledger_id' => $ledger->id,
            'debit' => 1000,
            'credit' => 0,
            'remarks' => 'Test entry',
        ]);

        return $voucher;
    }

    // ──────────────────────────────────────────────
    //  DayBookService::getAll() — uses attachLedgerInfo
    // ──────────────────────────────────────────────

    public function test_get_all_returns_vouchers_with_ledger_info_attached(): void
    {
        $this->createVoucherWithEntry();

        $response = $this->getJson('/api/day_books?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'voucher_no',
                        'amount',
                    ],
                ],
                'status',
                'code',
                'message',
            ]);
    }

    public function test_get_all_returns_empty_result_when_no_vouchers_exist(): void
    {
        $response = $this->getJson('/api/day_books?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_get_all_filters_by_search_term(): void
    {
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-001', 'remarks' => 'Office supplies']);

        $response = $this->getJson('/api/day_books?per_page=10&search=Office');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_get_all_filters_by_voucher_type_id(): void
    {
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-001']);

        $response = $this->getJson('/api/day_books?per_page=10&voucher_type_id=' . $this->voucherType->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_get_all_returns_correct_voucher_amount(): void
    {
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-001']);

        $response = $this->getJson('/api/day_books?per_page=10');

        $response->assertStatus(200);
        $this->assertSame(1000, (int) $response->json('data.data.0.amount'));
    }

    // ──────────────────────────────────────────────
    //  DayBookService::dayBooksSelf() — user-scoped
    // ──────────────────────────────────────────────

    public function test_day_books_self_returns_only_user_created_vouchers(): void
    {
        // Create a voucher by the current user
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-U1']);
        // Manually set created_by to this user
        Voucher::where('voucher_no', 'PAYM-U1')->update(['created_by' => $this->user->id]);

        // Create a voucher by a different user
        $otherUser = User::factory()->create();
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-U2']);
        Voucher::where('voucher_no', 'PAYM-U2')->update(['created_by' => $otherUser->id]);

        $response = $this->getJson('/api/day-books-self?per_page=10');

        $response->assertStatus(200);
        $vouchers = $response->json('data.data');
        $this->assertCount(1, $vouchers);
        $this->assertSame('PAYM-U1', $vouchers[0]['voucher_no']);
    }

    // ──────────────────────────────────────────────
    //  DayBookService::getUsedVoucherTypes()
    // ──────────────────────────────────────────────

    public function test_get_used_voucher_types_returns_types_in_use(): void
    {
        $this->createVoucherWithEntry();

        $response = $this->getJson('/api/day_books/used-voucher-types');

        $response->assertStatus(200);
        $types = $response->json('data');
        $this->assertCount(1, $types);
        $this->assertSame($this->voucherType->id, $types[0]['id']);
    }

    public function test_get_used_voucher_types_returns_empty_when_none_used(): void
    {
        $response = $this->getJson('/api/day_books/used-voucher-types');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // ──────────────────────────────────────────────
    //  Edge Cases
    // ──────────────────────────────────────────────

    public function test_get_all_handles_multiple_vouchers(): void
    {
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-001']);
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-002']);
        $this->createVoucherWithEntry(['voucher_no' => 'PAYM-003']);

        $response = $this->getJson('/api/day_books?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_get_all_handles_vouchers_outside_fiscal_year(): void
    {
        $this->createVoucherWithEntry([
            'voucher_no' => 'PAYM-001',
            'voucher_date' => '2024-03-31', // Before the FY start
            'fiscal_year_id' => 999,         // Different FY
        ]);

        $response = $this->getJson('/api/day_books?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));
    }
}
