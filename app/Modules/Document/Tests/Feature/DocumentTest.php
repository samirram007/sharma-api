<?php

namespace Modules\Document\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Document\Models\Document;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_documents(): void
    {
        $response = $this->getJson('/api/documents');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_document(): void
    {
        $data = ['name' => 'Test Document'];

        $response = $this->postJson('/api/documents', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('documents', $data);
    }

    public function test_can_show_document(): void
    {
        $Document = Document::factory()->create();

        $response = $this->getJson('/api/documents/'.$Document->id);
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

    public function test_can_update_document(): void
    {
        $Document = Document::factory()->create();
        $data = ['name' => 'Updated Document'];

        $response = $this->putJson('/api/documents/'.$Document->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('documents', $data);
    }

    public function test_can_delete_document(): void
    {
        $Document = Document::factory()->create();

        $response = $this->deleteJson('/api/documents/'.$Document->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('documents', ['id' => $Document->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/documents', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
