<?php

namespace Modules\StockCategory\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\StockCategory\Models\StockCategory;
use Tests\TestCase;

class StockCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_stock_categories(): void
    {
        $response = $this->getJson('/api/stock_categories');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_stock_category(): void
    {
        $data = ['name' => 'Test StockCategory'];

        $response = $this->postJson('/api/stock_categories', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_categories', $data);
    }

    public function test_can_show_stock_category(): void
    {
        $StockCategory = StockCategory::factory()->create();

        $response = $this->getJson('/api/stock_categories/'.$StockCategory->id);
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

    public function test_can_update_stock_category(): void
    {
        $StockCategory = StockCategory::factory()->create();
        $data = ['name' => 'Updated StockCategory'];

        $response = $this->putJson('/api/stock_categories/'.$StockCategory->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_categories', $data);
    }

    public function test_can_delete_stock_category(): void
    {
        $StockCategory = StockCategory::factory()->create();

        $response = $this->deleteJson('/api/stock_categories/'.$StockCategory->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('stock_categories', ['id' => $StockCategory->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/stock_categories', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
