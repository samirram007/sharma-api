<?php

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Models\Payment;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_payments(): void
    {
        $response = $this->getJson('/api/payments');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_payment(): void
    {
        $data = ['name' => 'Test Payment'];

        $response = $this->postJson('/api/payments', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('payments', $data);
    }

    public function test_can_show_payment(): void
    {
        $Payment = Payment::factory()->create();

        $response = $this->getJson('/api/payments/'.$Payment->id);
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

    public function test_can_update_payment(): void
    {
        $Payment = Payment::factory()->create();
        $data = ['name' => 'Updated Payment'];

        $response = $this->putJson('/api/payments/'.$Payment->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('payments', $data);
    }

    public function test_can_delete_payment(): void
    {
        $Payment = Payment::factory()->create();

        $response = $this->deleteJson('/api/payments/'.$Payment->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('payments', ['id' => $Payment->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/payments', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
