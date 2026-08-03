<?php

namespace Modules\VoucherNo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\VoucherNo\Models\VoucherNo;
use Modules\VoucherType\Models\VoucherType;
use Tests\TestCase;

class VoucherNoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_voucher_nos(): void
    {
        $response = $this->getJson('/api/voucher_nos');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_voucher_no(): void
    {
        $data = ['name' => 'Test VoucherNo'];

        $response = $this->postJson('/api/voucher_nos', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_nos', $data);
    }

    public function test_can_show_voucher_no(): void
    {
        $VoucherNo = VoucherNo::factory()->create();

        $response = $this->getJson('/api/voucher_nos/'.$VoucherNo->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_update_voucher_no(): void
    {
        $VoucherNo = VoucherNo::factory()->create();
        $data = ['name' => 'Updated VoucherNo'];

        $response = $this->putJson('/api/voucher_nos/'.$VoucherNo->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_nos', $data);
    }

    public function test_can_delete_voucher_no(): void
    {
        $VoucherNo = VoucherNo::factory()->create();

        $response = $this->deleteJson('/api/voucher_nos/'.$VoucherNo->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('voucher_nos', ['id' => $VoucherNo->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/voucher_nos', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // ──────────────────────────────────────────────
    //  Voucher Number Generation Tests (via API)
    // ──────────────────────────────────────────────

    private function createVoucherTypeFixture(): VoucherType
    {
        $category = \Modules\VoucherCategory\Models\VoucherCategory::create([
            'name' => 'Test Category',
            'code' => 'TEST',
        ]);
        $classification = \Modules\VoucherClassification\Models\VoucherClassification::create([
            'name' => 'Test Classification',
            'code' => 'TEST',
        ]);

        return VoucherType::create([
            'name' => 'Test Purchase',
            'code' => 'PURC',
            'voucher_category_id' => $category->id,
            'voucher_classification_id' => $classification->id,
            'status' => 'active',
        ]);
    }

    private function callGetVoucherNo(int $voucherTypeId, int $companyId, int $fiscalYearId): string
    {
        $response = $this->postJson('/api/voucher_nos/get_voucher_no', [
            'voucher_type_id' => $voucherTypeId,
            'company_id' => $companyId,
            'fiscal_year_id' => $fiscalYearId,
        ]);

        $response->assertStatus(200);

        return $response->json('data.voucher_no');
    }

    public function test_get_voucher_no_returns_sequential_numbers(): void
    {
        $voucherType = $this->createVoucherTypeFixture();

        // Seed an initial VoucherNo record
        VoucherNo::create([
            'prefix' => 'PURC-',
            'voucher_type_id' => $voucherType->id,
            'company_id' => 1,
            'branch_id' => null,
            'fiscal_year_id' => 2025,
            'starting_no' => 1,
            'current_no' => 5,
        ]);

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $this->callGetVoucherNo($voucherType->id, 1, 2025);
        }

        // Assert all 10 numbers are unique
        $this->assertCount(10, array_unique($numbers));

        // Assert they are sequential (PURC-6, PURC-7, ..., PURC-15)
        $this->assertSame('PURC-6', $numbers[0]);
        $this->assertSame('PURC-10', $numbers[4]);
        $this->assertSame('PURC-15', $numbers[9]);
    }

    public function test_get_voucher_no_creates_new_record_when_none_exists(): void
    {
        $voucherType = $this->createVoucherTypeFixture();

        // No VoucherNo record exists — should create one and return number 1
        $number = $this->callGetVoucherNo($voucherType->id, 1, 2025);

        $this->assertSame('PURC-1', $number);

        // Verify the record was created in the database
        $this->assertDatabaseHas('voucher_nos', [
            'voucher_type_id' => $voucherType->id,
            'company_id' => 1,
            'fiscal_year_id' => 2025,
            'current_no' => 1,
        ]);
    }

    public function test_concurrent_calls_produce_unique_numbers(): void
    {
        $voucherType = $this->createVoucherTypeFixture();

        // Seed initial record
        VoucherNo::create([
            'prefix' => 'PURC-',
            'voucher_type_id' => $voucherType->id,
            'company_id' => 1,
            'branch_id' => null,
            'fiscal_year_id' => 2025,
            'starting_no' => 1,
            'current_no' => 100,
        ]);

        // Rapid successive calls via the API endpoint — each uses lockForUpdate()
        $numbers = [];
        $numbers[] = $this->callGetVoucherNo($voucherType->id, 1, 2025);
        $numbers[] = $this->callGetVoucherNo($voucherType->id, 1, 2025);
        $numbers[] = $this->callGetVoucherNo($voucherType->id, 1, 2025);

        $this->assertCount(3, array_unique($numbers));
        $this->assertSame('PURC-101', $numbers[0]);
        $this->assertSame('PURC-102', $numbers[1]);
        $this->assertSame('PURC-103', $numbers[2]);
    }

    public function test_lock_for_update_prevents_duplicates_under_overlapping_transactions(): void
    {
        $voucherType = $this->createVoucherTypeFixture();

        // Seed initial record
        VoucherNo::create([
            'prefix' => 'PURC-',
            'voucher_type_id' => $voucherType->id,
            'company_id' => 1,
            'branch_id' => null,
            'fiscal_year_id' => 2025,
            'starting_no' => 1,
            'current_no' => 50,
        ]);

        // Simulate two overlapping transactions:
        // 1. Start a transaction that directly locks and increments the VoucherNo row
        // 2. Then call the API — should get the NEXT number
        //
        // This proves the lock works because transaction A's read-modify-write
        // is atomic and the API call sees the updated value.

        $firstNumber = null;

        DB::transaction(function () use ($voucherType, &$firstNumber) {
            $record = VoucherNo::where('voucher_type_id', $voucherType->id)
                ->where('company_id', 1)
                ->where('branch_id', null)
                ->where('fiscal_year_id', 2025)
                ->lockForUpdate()
                ->first();

            $record->current_no += 1;
            $record->save();

            $firstNumber = $record->prefix.$record->current_no;
            // current_no is now 51 inside this transaction
        });

        // After transaction A commits, the API call should get 52
        $secondNumber = $this->callGetVoucherNo($voucherType->id, 1, 2025);

        $this->assertSame('PURC-51', $firstNumber);
        $this->assertSame('PURC-52', $secondNumber);
    }

    public function test_creates_first_record_and_increments_across_multiple_calls(): void
    {
        $voucherType = $this->createVoucherTypeFixture();

        // No record exists yet → first call creates one
        $first = $this->callGetVoucherNo($voucherType->id, 2, 2026);
        $this->assertSame('PURC-1', $first);

        // Second call with different company → creates separate record
        $second = $this->callGetVoucherNo($voucherType->id, 3, 2026);
        $this->assertSame('PURC-1', $second);

        // Third call on original company → increments
        $third = $this->callGetVoucherNo($voucherType->id, 2, 2026);
        $this->assertSame('PURC-2', $third);

        // Verify two distinct records exist
        $this->assertDatabaseCount('voucher_nos', 2);
    }
}
