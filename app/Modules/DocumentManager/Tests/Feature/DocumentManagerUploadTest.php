<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\DocumentManager\Models\DocumentType;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Feature tests for POST /api/document-manager/upload (multipart file upload):
 *  - happy path stores the object on the local disk and creates the file node;
 *  - a re-upload of the same name becomes "name (2).ext" instead of colliding;
 *  - optional metadata (name / parent / visibility / category / description)
 *    is persisted; type_id is auto-derived from the extension;
 *  - a storage-layer failure surfaces as a 500 — this is the regression shape
 *    of the production incident where an un-migrated document_nodes column
 *    made every INSERT (and thus every upload) fail with a 500.
 */
beforeEach(function () {
    Storage::fake('local');

    $this->user = User::create([
        'name' => 'Upload Tester',
        'email' => 'upload-tester@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);
});

test('upload stores the file and creates a node', function () {
    $response = $this->withToken($this->token)
        ->post('/api/document-manager/upload', [
            'file' => UploadedFile::fake()->create('report.pdf', 12, 'application/pdf'),
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    expect($response->json('data.kind'))->toBe('file')
        ->and($response->json('data.name'))->toBe('report.pdf')
        ->and($response->json('data.extension'))->toBe('pdf')
        ->and($response->json('data.mimeType'))->toBe('application/pdf')
        ->and($response->json('data.visibility'))->toBe('private')
        ->and($response->json('data.ownerId'))->toBe($this->user->id);

    $storagePath = DocumentNode::query()->findOrFail($response->json('data.id'))->storage_path;
    expect($storagePath)->toStartWith('documents/')
        ->and(Storage::disk('local')->exists($storagePath))->toBeTrue();
});

test('uploading a duplicate name auto-renames to name (2)', function () {
    $this->withToken($this->token)
        ->post('/api/document-manager/upload', [
            'file' => UploadedFile::fake()->create('memo.txt', 4, 'text/plain'),
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $response = $this->withToken($this->token)
        ->post('/api/document-manager/upload', [
            'file' => UploadedFile::fake()->create('memo.txt', 6, 'text/plain'),
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    expect($response->json('data.name'))->toBe('memo (2).txt');
});

test('upload persists metadata and derives the document type from the extension', function () {
    // typeIdFor() maps extensions to DocumentType slugs — no seeder ships for
    // this table, so tests create the taxonomy rows themselves.
    $pdfType = DocumentType::query()->create(['name' => 'PDF', 'slug' => 'pdf']);
    $folder = DocumentNode::query()->create([
        'name' => 'Invoices',
        'kind' => DocumentNode::KIND_FOLDER,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => null,
        'owner_id' => $this->user->id,
    ]);

    $response = $this->withToken($this->token)
        ->post('/api/document-manager/upload', [
            'file' => UploadedFile::fake()->create('invoice.pdf', 8, 'application/pdf'),
            'parentId' => $folder->id,
            'visibility' => 'public',
            'description' => 'Q1 invoice bundle',
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $node = DocumentNode::query()->findOrFail($response->json('data.id'));

    expect($response->json('data.parentId'))->toBe($folder->id)
        ->and($response->json('data.visibility'))->toBe('public')
        ->and($response->json('data.description'))->toBe('Q1 invoice bundle')
        ->and($node->type_id)->toBe($pdfType->id);
});

test('a storage failure surfaces as a 500 instead of silently losing the file', function () {
    // Force the disk write to fail so upload() hits its RuntimeException —
    // the shape of the production incident (write/INSERT failure → 500).
    // Storage::fake does not support partial mocks, so swap the whole disk.
    Storage::shouldReceive('disk')
        ->with('local')
        ->andReturnUsing(fn () => throw new RuntimeException('disk full'));

    $this->withToken($this->token)
        ->post('/api/document-manager/upload', [
            'file' => UploadedFile::fake()->create('doomed.pdf', 4, 'application/pdf'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(500);

    expect(DocumentNode::query()->where('name', 'doomed.pdf')->exists())->toBeFalse();
});
