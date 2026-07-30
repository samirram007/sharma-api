<?php

namespace Modules\Supplier\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Supplier\Models\Supplier;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_suppliers(): void
    {
        $response = $this->getJson('/api/suppliers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_supplier(): void
    {
        $data = ['name' => 'Test Supplier'];

        $response = $this->postJson('/api/suppliers', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('suppliers', $data);
    }

    public function test_can_show_supplier(): void
    {
        $Supplier = Supplier::factory()->create();

        $response = $this->getJson('/api/suppliers/'.$Supplier->id);
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

    public function test_can_update_supplier(): void
    {
        $Supplier = Supplier::factory()->create();
        $data = ['name' => 'Updated Supplier'];

        $response = $this->putJson('/api/suppliers/'.$Supplier->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('suppliers', $data);
    }

    public function test_can_delete_supplier(): void
    {
        $Supplier = Supplier::factory()->create();

        $response = $this->deleteJson('/api/suppliers/'.$Supplier->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('suppliers', ['id' => $Supplier->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/suppliers', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
