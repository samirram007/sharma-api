<?php

namespace Modules\Currency\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Currency\Models\Currency;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_currencies(): void
    {
        $response = $this->getJson('/api/currencies');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_currency(): void
    {
        $data = ['name' => 'Test Currency'];

        $response = $this->postJson('/api/currencies', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('currencies', $data);
    }

    public function test_can_show_currency(): void
    {
        $Currency = Currency::factory()->create();

        $response = $this->getJson('/api/currencies/'.$Currency->id);
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

    public function test_can_update_currency(): void
    {
        $Currency = Currency::factory()->create();
        $data = ['name' => 'Updated Currency'];

        $response = $this->putJson('/api/currencies/'.$Currency->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('currencies', $data);
    }

    public function test_can_delete_currency(): void
    {
        $Currency = Currency::factory()->create();

        $response = $this->deleteJson('/api/currencies/'.$Currency->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('currencies', ['id' => $Currency->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/currencies', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
