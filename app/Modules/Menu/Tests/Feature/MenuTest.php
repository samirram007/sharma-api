<?php

namespace Modules\Menu\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\Menu\Models\Menu;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Menu Test User',
        'email' => 'menu-test@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);

    $this->feature = AppModuleFeature::create([
        'app_module_id' => 1,
        'name' => 'Menu Manager',
        'code' => 'MENU_MANAGER',
    ]);
});

test('GET /api/menu_tree returns 200 with children rendered as plain nested arrays', function () {
    $parent = Menu::create([
        'app_module_feature_id' => $this->feature->id,
        'menu_name' => 'Administration',
        'route' => '/administration',
        'sort_order' => 1,
        'status' => 'active',
        'is_visible' => true,
        'is_group' => true,
    ]);

    $child = Menu::create([
        'app_module_feature_id' => $this->feature->id,
        'menu_name' => 'Menu Manager',
        'route' => '/administration/menu_manager',
        'parent_id' => $parent->id,
        'sort_order' => 1,
        'status' => 'active',
        'is_visible' => true,
    ]);

    // Regression: /menu_tree used to 500 with
    // "Property [id] does not exist on this collection instance."
    $response = $this->withToken($this->token)->getJson('/api/menu_tree');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => [
                [
                    'id',
                    'menuName',
                    'parentId',
                    'sortOrder',
                    'children' => [
                        ['id', 'menuName', 'parentId', 'sortOrder', 'children'],
                    ],
                ],
            ],
        ]);

    $root = $response->json('data.0');

    expect($root['id'])->toBe($parent->id);
    expect($root['menuName'])->toBe('Administration');
    expect($root['children'])->toBeArray()->toHaveCount(1);
    expect($root['children'][0]['id'])->toBe($child->id);
    expect($root['children'][0]['menuName'])->toBe('Menu Manager');
    expect($root['children'][0]['parentId'])->toBe($parent->id);
    expect($root['children'][0]['children'])->toBeArray()->toBeEmpty();

    // camelCase only — no snake_case leakage
    $response->assertJsonMissingPath('data.0.menu_name');
    $response->assertJsonMissingPath('data.0.children.0.menu_name');
});

test('GET /api/menus omits children key when the relation is not loaded', function () {
    Menu::create([
        'app_module_feature_id' => $this->feature->id,
        'menu_name' => 'Standalone',
        'sort_order' => 1,
        'status' => 'active',
        'is_visible' => true,
    ]);

    $response = $this->withToken($this->token)->getJson('/api/menus');

    $response->assertOk()->assertJsonPath('success', true);
    expect($response->json('data.0'))->not->toHaveKey('children');
});

test('GET /api/menus/{id} returns a single menu entry', function () {
    $menu = Menu::create([
        'app_module_feature_id' => $this->feature->id,
        'menu_name' => 'Ledgers',
        'route' => '/masters/account_ledgers',
        'sort_order' => 1,
        'status' => 'active',
        'is_visible' => true,
    ]);

    $response = $this->withToken($this->token)->getJson("/api/menus/{$menu->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $menu->id)
        ->assertJsonPath('data.menuName', 'Ledgers');
});
