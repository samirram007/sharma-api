<?php

namespace Modules\StockJournalGodownEntryPurge\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\StockJournalGodownEntryPurge\Models\StockJournalGodownEntryPurge;
use Tests\TestCase;

class StockJournalGodownEntryPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_stock_journal_godown_entry_purges(): void
    {
        $response = $this->getJson('/api/stock_journal_godown_entry_purges');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_stock_journal_godown_entry_purge(): void
    {
        $data = ['name' => 'Test StockJournalGodownEntryPurge'];

        $response = $this->postJson('/api/stock_journal_godown_entry_purges', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_journal_godown_entry_purges', $data);
    }

    public function test_can_show_stock_journal_godown_entry_purge(): void
    {
        $StockJournalGodownEntryPurge = StockJournalGodownEntryPurge::factory()->create();

        $response = $this->getJson('/api/stock_journal_godown_entry_purges/'.$StockJournalGodownEntryPurge->id);
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

    public function test_can_update_stock_journal_godown_entry_purge(): void
    {
        $StockJournalGodownEntryPurge = StockJournalGodownEntryPurge::factory()->create();
        $data = ['name' => 'Updated StockJournalGodownEntryPurge'];

        $response = $this->putJson('/api/stock_journal_godown_entry_purges/'.$StockJournalGodownEntryPurge->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('stock_journal_godown_entry_purges', $data);
    }

    public function test_can_delete_stock_journal_godown_entry_purge(): void
    {
        $StockJournalGodownEntryPurge = StockJournalGodownEntryPurge::factory()->create();

        $response = $this->deleteJson('/api/stock_journal_godown_entry_purges/'.$StockJournalGodownEntryPurge->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('stock_journal_godown_entry_purges', ['id' => $StockJournalGodownEntryPurge->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/stock_journal_godown_entry_purges', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
