<?php

namespace Modules\AppModuleFeature\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppModule\Models\AppModule;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Feature Test User',
        'email' => 'feature-test@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);

    $this->module = AppModule::create([
        'name' => 'Test Module',
        'code' => 'TEST_MOD',
    ]);
});

test('can list app module features', function () {
    $feature = AppModuleFeature::create([
        'app_module_id' => $this->module->id,
        'name' => 'View',
        'code' => 'TEST_MOD_VIEW',
    ]);

    $response = $this->withToken($this->token)->getJson('/api/app_module_features');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => [['id', 'name', 'code', 'appModuleId']],
        ]);

    expect($response->json('data.0.id'))->toBe($feature->id);
    expect($response->json('data.0.appModuleId'))->toBe($this->module->id);

    // camelCase only — no snake_case leakage
    $response->assertJsonMissingPath('data.0.app_module_id');
});

test('can create app module feature', function () {
    $response = $this->withToken($this->token)->postJson('/api/app_module_features', [
        'name' => 'Create',
        'code' => 'TEST_MOD_CREATE',
        'app_module_id' => $this->module->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Create')
        ->assertJsonPath('data.appModuleId', $this->module->id);

    $this->assertDatabaseHas('app_module_features', [
        'name' => 'Create',
        'code' => 'TEST_MOD_CREATE',
    ]);
});

test('can show app module feature', function () {
    $feature = AppModuleFeature::create([
        'app_module_id' => $this->module->id,
        'name' => 'Show',
        'code' => 'TEST_MOD_SHOW',
    ]);

    $response = $this->withToken($this->token)->getJson("/api/app_module_features/{$feature->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $feature->id)
        ->assertJsonPath('data.code', 'TEST_MOD_SHOW');
});

test('can update app module feature', function () {
    $feature = AppModuleFeature::create([
        'app_module_id' => $this->module->id,
        'name' => 'Original',
        'code' => 'TEST_MOD_ORIG',
    ]);

    $response = $this->withToken($this->token)->putJson("/api/app_module_features/{$feature->id}", [
        'name' => 'Updated',
        'app_module_id' => $this->module->id,
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('app_module_features', [
        'id' => $feature->id,
        'name' => 'Updated',
    ]);
});

test('can delete app module feature', function () {
    $feature = AppModuleFeature::create([
        'app_module_id' => $this->module->id,
        'name' => 'Doomed',
        'code' => 'TEST_MOD_DEL',
    ]);

    $response = $this->withToken($this->token)->deleteJson("/api/app_module_features/{$feature->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'code', 'message']);

    $this->assertDatabaseMissing('app_module_features', ['id' => $feature->id]);
});

test('validation errors on create', function () {
    $response = $this->withToken($this->token)->postJson('/api/app_module_features', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'code', 'app_module_id']);
});
