<?php

namespace Modules\StockJournalSerialNoEntry\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\StockJournalSerialNoEntry\Models\StockJournalSerialNoEntry;
use Tests\TestCase;

class StockJournalSerialNoEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_stock_journal_serial_no_entries(): void
    {
        $response = $this->getJson('/api/stock_journal_serial_no_entries');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_stock_journal_serial_no_entry(): void
    {
        $data = ['name' => 'Test StockJournalSerialNoEntry'];

        $response = $this->postJson('/api/stock_journal_serial_no_entries', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_journal_serial_no_entries', $data);
    }

    public function test_can_show_stock_journal_serial_no_entry(): void
    {
        $StockJournalSerialNoEntry = StockJournalSerialNoEntry::factory()->create();

        $response = $this->getJson('/api/stock_journal_serial_no_entries/'.$StockJournalSerialNoEntry->id);
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

    public function test_can_update_stock_journal_serial_no_entry(): void
    {
        $StockJournalSerialNoEntry = StockJournalSerialNoEntry::factory()->create();
        $data = ['name' => 'Updated StockJournalSerialNoEntry'];

        $response = $this->putJson('/api/stock_journal_serial_no_entries/'.$StockJournalSerialNoEntry->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_journal_serial_no_entries', $data);
    }

    public function test_can_delete_stock_journal_serial_no_entry(): void
    {
        $StockJournalSerialNoEntry = StockJournalSerialNoEntry::factory()->create();

        $response = $this->deleteJson('/api/stock_journal_serial_no_entries/'.$StockJournalSerialNoEntry->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('stock_journal_serial_no_entries', ['id' => $StockJournalSerialNoEntry->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/stock_journal_serial_no_entries', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
