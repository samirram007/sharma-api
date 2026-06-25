<?php

namespace App\Modules\Status\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Status\Models\Status;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_statuses(): void
    {
        $response = $this->getJson('/api/statuses');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'status',
                     'code',
                     'message',
                 ]);
    }

    public function test_can_create_status(): void
    {
        $data = ['name' => 'Test Status', 'code' => 'TEST'];

        $response = $this->postJson('/api/statuses', $data);
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data',
                     'status',
                     'code',
                     'message',
                 ]);

        $this->assertDatabaseHas('statuses', $data);
    }

    public function test_can_show_status(): void
    {
        $status = Status::factory()->create();

        $response = $this->getJson('/api/statuses/' . $status->id);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'code',
                         'created_at',
                         'updated_at',
                     ],
                     'status',
                     'code',
                     'message',
                 ]);
    }

    public function test_can_update_status(): void
    {
        $status = Status::factory()->create();
        $data = ['name' => 'Updated Status'];

        $response = $this->putJson('/api/statuses/' . $status->id, $data);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'status',
                     'code',
                     'message',
                 ]);

        $this->assertDatabaseHas('statuses', $data);
    }

    public function test_can_delete_status(): void
    {
        $status = Status::factory()->create();

        $response = $this->deleteJson('/api/statuses/' . $status->id);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'code',
                     'message',
                 ]);

        $this->assertDatabaseMissing('statuses', ['id' => $status->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/statuses', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }
}
