<?php

namespace Modules\StockItemPrice\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\StockItemPrice\Models\StockItemPrice;
use Tests\TestCase;

class StockItemPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_stock_item_prices(): void
    {
        $response = $this->getJson('/api/stock_item_prices');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_stock_item_price(): void
    {
        $data = ['name' => 'Test StockItemPrice'];

        $response = $this->postJson('/api/stock_item_prices', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_item_prices', $data);
    }

    public function test_can_show_stock_item_price(): void
    {
        $StockItemPrice = StockItemPrice::factory()->create();

        $response = $this->getJson('/api/stock_item_prices/'.$StockItemPrice->id);
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

    public function test_can_update_stock_item_price(): void
    {
        $StockItemPrice = StockItemPrice::factory()->create();
        $data = ['name' => 'Updated StockItemPrice'];

        $response = $this->putJson('/api/stock_item_prices/'.$StockItemPrice->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_item_prices', $data);
    }

    public function test_can_delete_stock_item_price(): void
    {
        $StockItemPrice = StockItemPrice::factory()->create();

        $response = $this->deleteJson('/api/stock_item_prices/'.$StockItemPrice->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('stock_item_prices', ['id' => $StockItemPrice->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/stock_item_prices', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
