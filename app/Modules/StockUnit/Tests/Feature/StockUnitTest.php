<?php

namespace Modules\StockUnit\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\StockUnit\Models\StockUnit;
use Tests\TestCase;

class StockUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_stock_units(): void
    {
        $response = $this->getJson('/api/stock_units');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_stock_unit(): void
    {
        $data = ['name' => 'Test StockUnit'];

        $response = $this->postJson('/api/stock_units', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_units', $data);
    }

    public function test_can_show_stock_unit(): void
    {
        $StockUnit = StockUnit::factory()->create();

        $response = $this->getJson('/api/stock_units/'.$StockUnit->id);
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

    public function test_can_update_stock_unit(): void
    {
        $StockUnit = StockUnit::factory()->create();
        $data = ['name' => 'Updated StockUnit'];

        $response = $this->putJson('/api/stock_units/'.$StockUnit->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_units', $data);
    }

    public function test_can_delete_stock_unit(): void
    {
        $StockUnit = StockUnit::factory()->create();

        $response = $this->deleteJson('/api/stock_units/'.$StockUnit->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('stock_units', ['id' => $StockUnit->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/stock_units', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
