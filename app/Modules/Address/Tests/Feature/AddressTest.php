<?php

namespace Modules\Address\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Address\Models\Address;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_addresses(): void
    {
        $response = $this->getJson('/api/addresses');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_address(): void
    {
        $data = ['name' => 'Test Address'];

        $response = $this->postJson('/api/addresses', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('addresses', $data);
    }

    public function test_can_show_address(): void
    {
        $Address = Address::factory()->create();

        $response = $this->getJson('/api/addresses/'.$Address->id);
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

    public function test_can_update_address(): void
    {
        $Address = Address::factory()->create();
        $data = ['name' => 'Updated Address'];

        $response = $this->putJson('/api/addresses/'.$Address->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('addresses', $data);
    }

    public function test_can_delete_address(): void
    {
        $Address = Address::factory()->create();

        $response = $this->deleteJson('/api/addresses/'.$Address->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('addresses', ['id' => $Address->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/addresses', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
