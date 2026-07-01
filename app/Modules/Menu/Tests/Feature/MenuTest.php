<?php

namespace Modules\Menu\Tests\Feature;

use Modules\Menu\Models\Menu;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;


class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_app_module_features(): void
    {
        $response = $this->getJson('/api/menus');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'status',
                     'code',
                     'message'
                 ]);
    }

    public function test_can_create_Menu(): void
    {
        $data = ['name' => 'Test Menu'];

        $response = $this->postJson('/api/menus', $data);
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data',
                     'status',
                     'code',
                     'message'
                 ]);

        $this->assertDatabaseHas('menus', $data);
    }

    public function test_can_show_Menu(): void
    {
        $Menu = Menu::factory()->create();

        $response = $this->getJson('/api/menus/' . $Menu->id);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'created_at',
                         'updated_at'
                     ],
                     'status',
                     'code',
                     'message'
                 ]);
    }

    public function test_can_update_Menu(): void
    {
        $Menu = Menu::factory()->create();
        $data = ['name' => 'Updated Menu'];

        $response = $this->putJson('/api/menus/' . $Menu->id, $data);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'status',
                     'code',
                     'message'
                 ]);

        $this->assertDatabaseHas('menus', $data);
    }

    public function test_can_delete_Menu(): void
    {
        $Menu = Menu::factory()->create();

        $response = $this->deleteJson('/api/menus/' . $Menu->id);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'code',
                     'message'
                 ]);

        $this->assertDatabaseMissing('menus', ['id' => $Menu->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/menus', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }
}
