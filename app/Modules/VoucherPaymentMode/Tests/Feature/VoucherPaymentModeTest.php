<?php

namespace Modules\VoucherPaymentMode\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VoucherPaymentMode\Models\VoucherPaymentMode;
use Tests\TestCase;

class VoucherPaymentModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_voucher_payment_modes(): void
    {
        $response = $this->getJson('/api/voucher_payment_modes');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_voucher_payment_mode(): void
    {
        $data = ['name' => 'Test VoucherPaymentMode'];

        $response = $this->postJson('/api/voucher_payment_modes', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_payment_modes', $data);
    }

    public function test_can_show_voucher_payment_mode(): void
    {
        $VoucherPaymentMode = VoucherPaymentMode::factory()->create();

        $response = $this->getJson('/api/voucher_payment_modes/'.$VoucherPaymentMode->id);
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

    public function test_can_update_voucher_payment_mode(): void
    {
        $VoucherPaymentMode = VoucherPaymentMode::factory()->create();
        $data = ['name' => 'Updated VoucherPaymentMode'];

        $response = $this->putJson('/api/voucher_payment_modes/'.$VoucherPaymentMode->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_payment_modes', $data);
    }

    public function test_can_delete_voucher_payment_mode(): void
    {
        $VoucherPaymentMode = VoucherPaymentMode::factory()->create();

        $response = $this->deleteJson('/api/voucher_payment_modes/'.$VoucherPaymentMode->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('voucher_payment_modes', ['id' => $VoucherPaymentMode->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/voucher_payment_modes', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
