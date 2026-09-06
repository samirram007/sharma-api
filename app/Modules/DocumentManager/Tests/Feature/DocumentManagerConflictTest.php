<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Feature tests for DocumentManager name-conflict semantics:
 *  - PATCH rename onto an existing sibling name must fail with 422.
 *  - POST /conflicts pre-check reports destination name collisions.
 *  - move/copy with conflict=replace deletes the destination sibling first.
 *  - move/copy with conflict=rename keeps both ("name (2)").
 *  - move/copy WITHOUT a conflict strategy must refuse a silent overwrite.
 *
 * Same request pattern as DocumentManagerMoveCopyTest: real jwt.cookies
 * middleware with a JWT minted for a freshly created user.
 */
beforeEach(function () {
    Storage::fake('local');

    $this->user = User::create([
        'name' => 'Conflict Tester',
        'email' => 'conflict-tester@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);
});

function conflictFolder(string $name, ?int $parentId = null): DocumentNode
{
    return DocumentNode::query()->create([
        'name' => $name,
        'kind' => DocumentNode::KIND_FOLDER,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $parentId,
        'owner_id' => auth()->id() ?? 1,
    ]);
}

function conflictFile(string $name, ?int $parentId, string $content = 'payload'): DocumentNode
{
    // storeAs('documents', …) already prefixes the disk root — the DB row must
    // record the same relative path the storage actually holds.
    $path = 'tests/'.uniqid().'.txt';
    UploadedFile::fake()->createWithContent($name, $content)->storeAs('documents', $path, 'local');
    $storagePath = 'documents/'.$path;

    return DocumentNode::query()->create([
        'name' => $name,
        'kind' => DocumentNode::KIND_FILE,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $parentId,
        'owner_id' => auth()->id() ?? 1,
        'mime_type' => 'text/plain',
        'extension' => 'txt',
        'storage_path' => $storagePath,
        'size_bytes' => strlen($content),
    ]);
}

// ---------------------------------------------------------------------------
//  Rename guard — PATCH with an existing sibling name
// ---------------------------------------------------------------------------

test('rename onto an existing sibling name fails with 422', function () {
    $parent = conflictFolder('parent');
    conflictFolder('taken', $parent->id);
    $node = conflictFolder('original', $parent->id);

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$node->id}", ['name' => 'taken'])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'An item named "taken" already exists in this folder.']);

    expect($node->fresh()->name)->toBe('original');
});

test('rename to a free name still succeeds', function () {
    $parent = conflictFolder('parent');
    conflictFolder('taken', $parent->id);
    $node = conflictFolder('original', $parent->id);

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$node->id}", ['name' => 'free'])
        ->assertOk();

    expect($node->fresh()->name)->toBe('free');
});

test('rename keeps its own name without self-conflict', function () {
    $node = conflictFolder('same-name');

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$node->id}", ['name' => 'same-name'])
        ->assertOk();

    expect($node->fresh()->name)->toBe('same-name');
});

// ---------------------------------------------------------------------------
//  Conflicts pre-check — POST /conflicts
// ---------------------------------------------------------------------------

test('conflicts endpoint reports a destination name collision with details', function () {
    $destination = conflictFolder('destination');
    $existing = conflictFile('report.txt', $destination->id, 'old content');
    $moved = conflictFile('report.txt', null, 'new content');

    $response = $this->withToken($this->token)
        ->postJson('/api/document-manager/conflicts', [
            'ids' => [$moved->id],
            'parentId' => $destination->id,
        ])
        ->assertOk();

    $conflicts = $response->json('data');
    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['movedId'])->toBe($moved->id)
        ->and($conflicts[0]['movedName'])->toBe('report.txt')
        ->and($conflicts[0]['existingId'])->toBe($existing->id)
        ->and($conflicts[0]['existingSizeBytes'])->toBe(11);
});

test('conflicts endpoint reports nothing when names are free', function () {
    $destination = conflictFolder('destination');
    $moved = conflictFile('unique.txt', null);

    $this->withToken($this->token)
        ->postJson('/api/document-manager/conflicts', [
            'ids' => [$moved->id],
            'parentId' => $destination->id,
        ])
        ->assertOk()
        ->assertJsonPath('data', []);
});

test('conflicts endpoint checks several ids at once', function () {
    $destination = conflictFolder('destination');
    conflictFile('a.txt', $destination->id);
    $b = conflictFile('b.txt', null);
    $c = conflictFile('c.txt', null);

    $response = $this->withToken($this->token)
        ->postJson('/api/document-manager/conflicts', [
            'ids' => [$b->id, $c->id],
            'parentId' => $destination->id,
        ])
        ->assertOk();

    // b.txt is free; a collision would only exist if a.txt were among ids.
    expect($response->json('data'))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
//  Move + conflict strategy
// ---------------------------------------------------------------------------

test('plain move onto an existing name is refused with 422', function () {
    $destination = conflictFolder('destination');
    conflictFile('report.txt', $destination->id, 'old');
    $moved = conflictFile('report.txt', null, 'new');

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$moved->id}", ['parentId' => $destination->id])
        ->assertStatus(422);

    expect($moved->fresh()->parent_id)->toBeNull()
        ->and($moved->fresh()->name)->toBe('report.txt');
});

test('move with conflict=replace deletes the destination sibling and takes its name', function () {
    $destination = conflictFolder('destination');
    $existing = conflictFile('report.txt', $destination->id, 'old content');
    $moved = conflictFile('report.txt', null, 'new content');

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$moved->id}", [
            'parentId' => $destination->id,
            'conflict' => 'replace',
        ])
        ->assertOk();

    expect(DocumentNode::query()->whereKey($existing->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists($existing->storage_path))->toBeFalse()
        ->and($moved->fresh()->parent_id)->toBe($destination->id)
        ->and($moved->fresh()->name)->toBe('report.txt');
});

test('move with conflict=rename keeps both as name (2)', function () {
    $destination = conflictFolder('destination');
    conflictFile('report.txt', $destination->id, 'old');
    $moved = conflictFile('report.txt', null, 'new');

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$moved->id}", [
            'parentId' => $destination->id,
            'conflict' => 'rename',
        ])
        ->assertOk();

    expect($moved->fresh()->parent_id)->toBe($destination->id)
        ->and($moved->fresh()->name)->toBe('report (2).txt')
        // The original sibling is untouched.
        ->and(DocumentNode::query()->where('parent_id', $destination->id)->where('name', 'report.txt')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
//  Copy + conflict strategy
// ---------------------------------------------------------------------------

test('copy defaults to rename — keeping both without any prompt', function () {
    $destination = conflictFolder('destination');
    conflictFile('report.txt', $destination->id, 'old');
    $source = conflictFile('report.txt', null, 'new');

    $response = $this->withToken($this->token)
        ->postJson("/api/document-manager/nodes/{$source->id}/copy", ['parentId' => $destination->id])
        ->assertCreated();

    expect($response->json('data.name'))->toBe('report (2).txt');
});

test('copy with conflict=replace deletes the destination sibling first', function () {
    $destination = conflictFolder('destination');
    $existing = conflictFile('report.txt', $destination->id, 'old content');
    $source = conflictFile('report.txt', null, 'new content');

    $response = $this->withToken($this->token)
        ->postJson("/api/document-manager/nodes/{$source->id}/copy", [
            'parentId' => $destination->id,
            'conflict' => 'replace',
        ])
        ->assertCreated();

    expect(DocumentNode::query()->whereKey($existing->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists($existing->storage_path))->toBeFalse()
        ->and($response->json('data.name'))->toBe('report.txt');
});

test('replace also works for folders and removes the old subtree', function () {
    $destination = conflictFolder('destination');
    $oldFolder = conflictFolder('project', $destination->id);
    conflictFile('nested.txt', $oldFolder->id, 'inside old');
    $source = conflictFolder('project');

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$source->id}", [
            'parentId' => $destination->id,
            'conflict' => 'replace',
        ])
        ->assertOk();

    expect(DocumentNode::query()->whereKey($oldFolder->id)->exists())->toBeFalse()
        ->and(DocumentNode::query()->where('parent_id', $destination->id)->where('name', 'project')->get())
        ->toHaveCount(1)
        ->and($source->fresh()->parent_id)->toBe($destination->id);
});
