<?php

namespace Modules\State\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\State\Models\State;
use Tests\TestCase;

class StateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_states(): void
    {
        $response = $this->getJson('/api/states');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_state(): void
    {
        $data = ['name' => 'Test State'];

        $response = $this->postJson('/api/states', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('states', $data);
    }

    public function test_can_show_state(): void
    {
        $State = State::factory()->create();

        $response = $this->getJson('/api/states/'.$State->id);
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

    public function test_can_update_state(): void
    {
        $State = State::factory()->create();
        $data = ['name' => 'Updated State'];

        $response = $this->putJson('/api/states/'.$State->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('states', $data);
    }

    public function test_can_delete_state(): void
    {
        $State = State::factory()->create();

        $response = $this->deleteJson('/api/states/'.$State->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('states', ['id' => $State->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/states', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
