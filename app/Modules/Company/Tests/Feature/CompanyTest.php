<?php

namespace Modules\Company\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Company\Models\Company;
use Modules\CompanyType\Models\CompanyType;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Company Test User',
        'email' => 'company-test@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);

    $this->companyType = CompanyType::create([
        'name' => 'Private Limited',
        'code' => 'PVT',
    ]);
});

test('can list companies', function () {
    $company = Company::create([
        'name' => 'Test Company',
        'code' => 'TC001',
        'company_type_id' => $this->companyType->id,
    ]);

    $response = $this->withToken($this->token)->getJson('/api/companies');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => [['id', 'name', 'code', 'companyTypeId']],
        ]);

    expect($response->json('data.0.id'))->toBe($company->id);
    expect($response->json('data.0.name'))->toBe('Test Company');
    expect($response->json('data.0.companyTypeId'))->toBe($this->companyType->id);

    // camelCase only — no snake_case leakage
    $response->assertJsonMissingPath('data.0.company_type_id');
});

test('can create company', function () {
    $response = $this->withToken($this->token)->postJson('/api/companies', [
        'name' => 'Created Company',
        'code' => 'CC001',
        'company_type_id' => $this->companyType->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Created Company')
        ->assertJsonPath('data.companyTypeId', $this->companyType->id);

    $this->assertDatabaseHas('companies', ['name' => 'Created Company']);
});

test('can show company', function () {
    $company = Company::create([
        'name' => 'Show Company',
        'code' => 'SC001',
        'company_type_id' => $this->companyType->id,
    ]);

    $response = $this->withToken($this->token)->getJson("/api/companies/{$company->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $company->id)
        ->assertJsonPath('data.name', 'Show Company');

    $response->assertJsonMissingPath('data.company_type_id');
});

test('can update company', function () {
    $company = Company::create([
        'name' => 'Original Company',
        'code' => 'OC001',
        'company_type_id' => $this->companyType->id,
    ]);

    $response = $this->withToken($this->token)->putJson("/api/companies/{$company->id}", [
        'name' => 'Updated Company',
        'company_type_id' => $this->companyType->id,
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Updated Company']);
});

test('can delete company', function () {
    $company = Company::create([
        'name' => 'Doomed Company',
        'code' => 'DC001',
        'company_type_id' => $this->companyType->id,
    ]);

    $response = $this->withToken($this->token)->deleteJson("/api/companies/{$company->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'code', 'message']);

    $this->assertDatabaseMissing('companies', ['id' => $company->id]);
});

test('validation errors on create', function () {
    $response = $this->withToken($this->token)->postJson('/api/companies', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'company_type_id']);
});
