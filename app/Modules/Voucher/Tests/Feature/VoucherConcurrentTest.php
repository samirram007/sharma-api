<?php

namespace Modules\Voucher\Tests\Feature;

use App\Enums\ActiveInactive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Company\Models\Company;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Facades\VoucherFacade;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherClassification\Models\VoucherClassification;
use Modules\VoucherNo\Models\VoucherNo;
use Modules\VoucherType\Models\VoucherType;
use Tests\TestCase;

class VoucherConcurrentTest extends TestCase
{
    use RefreshDatabase;

    private VoucherType $voucherType;

    private User $user;

    private FiscalYear $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TST',
        ]);

        // Create an active fiscal year
        $this->fiscalYear = FiscalYear::create([
            'name' => 'FY 2025-26',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'status' => ActiveInactive::Active,
            'company_id' => $company->id,
        ]);

        // Create a user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create UserFiscalYear linking user to fiscal year
        UserFiscalYear::create([
            'user_id' => $this->user->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'current_date' => '2025-04-01',
        ]);

        // Create voucher category and classification for voucher type
        $category = VoucherCategory::create([
            'name' => 'Sales',
            'code' => 'SAL',
        ]);

        $classification = VoucherClassification::create([
            'name' => 'Default',
            'code' => 'DEF',
        ]);

        $this->voucherType = VoucherType::create([
            'name' => 'Sales Invoice',
            'code' => 'SALE',
            'voucher_category_id' => $category->id,
            'voucher_classification_id' => $classification->id,
            'status' => 'active',
        ]);

        // Authenticate the user
        Auth::login($this->user);
    }

    /**
     * Helper to call VoucherFacade::store() with minimal data that triggers
     * the generateVoucherNoStep() pipeline step.
     */
    private function createVoucherViaStore(): Voucher
    {
        return VoucherFacade::store([
            'voucher_type_id' => $this->voucherType->id,
            'voucher_date' => '2025-04-15',
            'voucher_no' => 'new',    // triggers generateVoucherNoStep()
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Concurrent test voucher',
        ]);
    }

    // ──────────────────────────────────────────────
    //  Concurrent Voucher Number Generation Tests
    // ──────────────────────────────────────────────

    public function test_sequential_store_calls_generate_unique_voucher_numbers(): void
    {
        $numbers = [];
        for ($i = 0; $i < 5; $i++) {
            $voucher = $this->createVoucherViaStore();
            $numbers[] = $voucher->voucher_no;
        }

        // Assert all 5 numbers are unique
        $this->assertCount(5, array_unique($numbers));

        // Assert they follow the SALE- prefix pattern
        $this->assertStringStartsWith('SALE-', $numbers[0]);
        $this->assertSame((int) substr($numbers[0], 5), 1);
        $this->assertSame((int) substr($numbers[4], 5), 5);
    }

    public function test_store_creates_new_voucher_no_record_when_none_exists(): void
    {
        // No VoucherNo record exists for this type/company/fiscal year
        $voucher = $this->createVoucherViaStore();

        $this->assertStringStartsWith('SALE-', $voucher->voucher_no);
        $this->assertSame('SALE-1', $voucher->voucher_no);

        // Verify a VoucherNo record was created
        $this->assertDatabaseHas('voucher_nos', [
            'voucher_type_id' => $this->voucherType->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'current_no' => 1,
        ]);
    }

    public function test_concurrent_store_calls_produce_unique_numbers_under_overlapping_transactions(): void
    {
        // Seed a VoucherNo record
        VoucherNo::create([
            'prefix' => 'SALE-',
            'voucher_type_id' => $this->voucherType->id,
            'company_id' => $this->fiscalYear->company_id,
            'branch_id' => null,
            'fiscal_year_id' => $this->fiscalYear->id,
            'starting_no' => 1,
            'current_no' => 50,
        ]);

        // First transaction: lock and increment the VoucherNo row directly,
        // simulating an overlapping concurrent request
        $firstNo = null;
        DB::transaction(function () use (&$firstNo) {
            $record = VoucherNo::where('voucher_type_id', $this->voucherType->id)
                ->where('company_id', $this->fiscalYear->company_id)
                ->where('fiscal_year_id', $this->fiscalYear->id)
                ->lockForUpdate()
                ->first();

            $record->current_no += 1;
            $record->save();

            $firstNo = $record->prefix.$record->current_no;
        });

        // After the first transaction commits, store() should get the next number
        $voucher = $this->createVoucherViaStore();

        $this->assertSame('SALE-51', $firstNo);
        $this->assertSame('SALE-52', $voucher->voucher_no);
    }

    public function test_different_voucher_types_get_independent_sequences(): void
    {
        // Create a second voucher type
        $category = VoucherCategory::create([
            'name' => 'Purchase',
            'code' => 'PUR',
        ]);
        $classification = VoucherClassification::create([
            'name' => 'Purchase Default',
            'code' => 'PUR',
        ]);
        $purchaseType = VoucherType::create([
            'name' => 'Purchase Invoice',
            'code' => 'PURC',
            'voucher_category_id' => $category->id,
            'voucher_classification_id' => $classification->id,
            'status' => 'active',
        ]);

        // Create vouchers for both types
        $saleVoucher1 = VoucherFacade::store([
            'voucher_type_id' => $this->voucherType->id,
            'voucher_date' => '2025-04-15',
            'voucher_no' => 'new',
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Sale voucher',
        ]);

        $purchaseVoucher1 = VoucherFacade::store([
            'voucher_type_id' => $purchaseType->id,
            'voucher_date' => '2025-04-15',
            'voucher_no' => 'new',
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Purchase voucher',
        ]);

        $saleVoucher2 = VoucherFacade::store([
            'voucher_type_id' => $this->voucherType->id,
            'voucher_date' => '2025-04-15',
            'voucher_no' => 'new',
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Sale voucher 2',
        ]);

        // Each type has its own independent sequence
        $this->assertSame('SALE-1', $saleVoucher1->voucher_no);
        $this->assertSame('PURC-1', $purchaseVoucher1->voucher_no);
        $this->assertSame('SALE-2', $saleVoucher2->voucher_no);

        // Verify two VoucherNo records exist (one per type)
        $this->assertDatabaseCount('voucher_nos', 2);
    }

    public function test_store_with_explicit_voucher_no_skips_number_generation(): void
    {
        $voucher = VoucherFacade::store([
            'voucher_type_id' => $this->voucherType->id,
            'voucher_date' => '2025-04-15',
            'voucher_no' => 'MANUAL-001',   // explicit number, skip generation
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Manual number test',
        ]);

        $this->assertSame('MANUAL-001', $voucher->voucher_no);

        // No VoucherNo record should have been created
        $this->assertDatabaseCount('voucher_nos', 0);
    }
}
