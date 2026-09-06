<?php

namespace Modules\Ticket\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ticket\Models\Ticket;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tickets(): void
    {
        $response = $this->getJson('/api/tickets');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_ticket(): void
    {
        $data = [
            'subject' => 'Test Ticket',
            'description' => 'This is a test ticket description.',
            'priority' => 'medium',
            'category' => 'general',
        ];

        $response = $this->postJson('/api/tickets', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('tickets', ['subject' => 'Test Ticket']);
    }

    public function test_can_show_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Show Ticket',
            'description' => 'Show me this ticket.',
            'priority' => 'medium',
            'created_by' => 1,
        ]);

        $response = $this->getJson('/api/tickets/'.$ticket->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'subject', 'description', 'status', 'priority',
                    'createdAt', 'updatedAt',
                ],
                'status', 'code', 'message',
            ]);
    }

    public function test_can_update_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Old Subject',
            'description' => 'Old description.',
            'priority' => 'low',
            'created_by' => 1,
        ]);

        $data = ['subject' => 'Updated Subject'];
        $response = $this->putJson('/api/tickets/'.$ticket->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data', 'status', 'code', 'message',
            ]);

        $this->assertDatabaseHas('tickets', ['subject' => 'Updated Subject']);
    }

    public function test_can_delete_ticket(): void
    {
        $ticket = Ticket::create([
            'subject' => 'To Delete',
            'description' => 'Delete me.',
            'priority' => 'low',
            'created_by' => 1,
        ]);

        $response = $this->deleteJson('/api/tickets/'.$ticket->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message',
            ]);

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/tickets', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'description']);
    }
}
