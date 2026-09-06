<?php

namespace Modules\Faq\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Faq\Models\Faq;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_faqs(): void
    {
        $response = $this->getJson('/api/faqs');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_faq(): void
    {
        $data = [
            'question' => 'What is this?',
            'answer' => 'This is a test FAQ.',
            'category' => 'general',
        ];

        $response = $this->postJson('/api/faqs', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('faqs', ['question' => 'What is this?']);
    }

    public function test_can_show_faq(): void
    {
        $faq = Faq::create([
            'question' => 'Test Question',
            'answer' => 'Test Answer',
            'category' => 'general',
        ]);

        $response = $this->getJson('/api/faqs/'.$faq->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'question', 'answer', 'category',
                    'createdAt', 'updatedAt',
                ],
                'status', 'code', 'message',
            ]);
    }

    public function test_can_update_faq(): void
    {
        $faq = Faq::create([
            'question' => 'Old Question',
            'answer' => 'Old Answer',
            'category' => 'general',
        ]);

        $data = ['question' => 'Updated Question'];
        $response = $this->putJson('/api/faqs/'.$faq->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data', 'status', 'code', 'message',
            ]);

        $this->assertDatabaseHas('faqs', ['question' => 'Updated Question']);
    }

    public function test_can_delete_faq(): void
    {
        $faq = Faq::create([
            'question' => 'To Delete',
            'answer' => 'Delete me',
            'category' => 'general',
        ]);

        $response = $this->deleteJson('/api/faqs/'.$faq->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message',
            ]);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/faqs', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'answer']);
    }
}
