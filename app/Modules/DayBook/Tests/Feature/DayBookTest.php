<?php

namespace Modules\DayBook\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DayBook\Models\DayBook;
use Tests\TestCase;

class DayBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_day_books(): void
    {
        $response = $this->getJson('/api/day_books');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_day_book(): void
    {
        $data = ['name' => 'Test DayBook'];

        $response = $this->postJson('/api/day_books', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('day_books', $data);
    }

    public function test_can_show_day_book(): void
    {
        $DayBook = DayBook::factory()->create();

        $response = $this->getJson('/api/day_books/'.$DayBook->id);
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

    public function test_can_update_day_book(): void
    {
        $DayBook = DayBook::factory()->create();
        $data = ['name' => 'Updated DayBook'];

        $response = $this->putJson('/api/day_books/'.$DayBook->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('day_books', $data);
    }

    public function test_can_delete_day_book(): void
    {
        $DayBook = DayBook::factory()->create();

        $response = $this->deleteJson('/api/day_books/'.$DayBook->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('day_books', ['id' => $DayBook->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/day_books', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
