<?php

namespace Modules\Journal\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Journal\Models\Journal;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_journals(): void
    {
        $response = $this->getJson('/api/journals');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_journal(): void
    {
        $data = ['name' => 'Test Journal'];

        $response = $this->postJson('/api/journals', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('journals', $data);
    }

    public function test_can_show_journal(): void
    {
        $Journal = Journal::factory()->create();

        $response = $this->getJson('/api/journals/'.$Journal->id);
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

    public function test_can_update_journal(): void
    {
        $Journal = Journal::factory()->create();
        $data = ['name' => 'Updated Journal'];

        $response = $this->putJson('/api/journals/'.$Journal->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('journals', $data);
    }

    public function test_can_delete_journal(): void
    {
        $Journal = Journal::factory()->create();

        $response = $this->deleteJson('/api/journals/'.$Journal->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('journals', ['id' => $Journal->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/journals', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
