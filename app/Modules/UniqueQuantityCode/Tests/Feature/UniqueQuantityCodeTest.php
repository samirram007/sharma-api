<?php

namespace Modules\UniqueQuantityCode\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\UniqueQuantityCode\Models\UniqueQuantityCode;
use Tests\TestCase;

class UniqueQuantityCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_unique_quantity_codes(): void
    {
        $response = $this->getJson('/api/unique_quantity_codes');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_unique_quantity_code(): void
    {
        $data = ['name' => 'Test UniqueQuantityCode'];

        $response = $this->postJson('/api/unique_quantity_codes', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('unique_quantity_codes', $data);
    }

    public function test_can_show_unique_quantity_code(): void
    {
        $UniqueQuantityCode = UniqueQuantityCode::factory()->create();

        $response = $this->getJson('/api/unique_quantity_codes/'.$UniqueQuantityCode->id);
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

    public function test_can_update_unique_quantity_code(): void
    {
        $UniqueQuantityCode = UniqueQuantityCode::factory()->create();
        $data = ['name' => 'Updated UniqueQuantityCode'];

        $response = $this->putJson('/api/unique_quantity_codes/'.$UniqueQuantityCode->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('unique_quantity_codes', $data);
    }

    public function test_can_delete_unique_quantity_code(): void
    {
        $UniqueQuantityCode = UniqueQuantityCode::factory()->create();

        $response = $this->deleteJson('/api/unique_quantity_codes/'.$UniqueQuantityCode->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('unique_quantity_codes', ['id' => $UniqueQuantityCode->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/unique_quantity_codes', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
