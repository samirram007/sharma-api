<?php

use Modules\DocumentManager\Models\DocumentNode;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Feature tests for DocumentManager shortcuts (kind = 'shortcut'):
 *  - POST /shortcuts creates a link node to a folder or file;
 *  - shortcuts may not target other shortcuts (no chains);
 *  - GET /shortcuts/{shortcut}/resolve returns the target node;
 *  - a deleted target turns the link broken (410 on resolve);
 *  - broken links can be removed by their owner;
 *  - auto-named shortcuts may share the target's name (rename guard exempt).
 */
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Shortcut Tester',
        'email' => 'shortcut-tester@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);
});

function shortcutFolder(string $name, ?int $parentId = null): DocumentNode
{
    return DocumentNode::query()->create([
        'name' => $name,
        'kind' => DocumentNode::KIND_FOLDER,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $parentId,
        'owner_id' => auth()->id() ?? 1,
    ]);
}

// ---------------------------------------------------------------------------
//  Create — POST /shortcuts
// ---------------------------------------------------------------------------

test('creates a shortcut to a folder at the root', function () {
    $folder = shortcutFolder('Reports');

    $response = $this->withToken($this->token)
        ->postJson('/api/document-manager/shortcuts', ['targetId' => $folder->id])
        ->assertCreated();

    expect($response->json('data.kind'))->toBe('shortcut')
        ->and($response->json('data.name'))->toBe('Reports')
        ->and($response->json('data.targetId'))->toBe($folder->id)
        ->and($response->json('data.parentId'))->toBeNull()
        ->and($response->json('data.target.name'))->toBe('Reports');
});

test('creates a named shortcut inside a specific folder', function () {
    $destination = shortcutFolder('destination');
    $file = DocumentNode::query()->create([
        'name' => 'q1.xlsx',
        'kind' => DocumentNode::KIND_FILE,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => null,
        'owner_id' => $this->user->id,
        'extension' => 'xlsx',
        'size_bytes' => 12,
    ]);

    $response = $this->withToken($this->token)
        ->postJson('/api/document-manager/shortcuts', [
            'targetId' => $file->id,
            'parentId' => $destination->id,
            'name' => 'Q1 link',
        ])
        ->assertCreated();

    expect($response->json('data.name'))->toBe('Q1 link')
        ->and($response->json('data.parentId'))->toBe($destination->id);
});

test('a shortcut cannot target another shortcut', function () {
    $folder = shortcutFolder('plain');
    $this->withToken($this->token)
        ->postJson('/api/document-manager/shortcuts', ['targetId' => $folder->id])
        ->assertCreated();
    $firstShortcut = DocumentNode::query()->where('kind', 'shortcut')->first();

    $this->withToken($this->token)
        ->postJson('/api/document-manager/shortcuts', ['targetId' => $firstShortcut->id])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
//  Resolve — GET /shortcuts/{shortcut}/resolve
// ---------------------------------------------------------------------------

test('resolve returns the target node', function () {
    $folder = shortcutFolder('Projects');
    $shortcut = DocumentNode::query()->create([
        'name' => 'Projects',
        'kind' => DocumentNode::KIND_SHORTCUT,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'target_id' => $folder->id,
        'owner_id' => $this->user->id,
    ]);

    $this->withToken($this->token)
        ->getJson("/api/document-manager/shortcuts/{$shortcut->id}/resolve")
        ->assertOk()
        ->assertJsonPath('data.id', $folder->id)
        ->assertJsonPath('data.kind', 'folder');
});

test('resolve reports 410 when the target is gone', function () {
    $folder = shortcutFolder('Doomed');
    $shortcut = DocumentNode::query()->create([
        'name' => 'Doomed',
        'kind' => DocumentNode::KIND_SHORTCUT,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'target_id' => $folder->id,
        'owner_id' => $this->user->id,
    ]);
    $folder->delete(); // FK nulls target_id → broken link

    $this->withToken($this->token)
        ->getJson("/api/document-manager/shortcuts/{$shortcut->id}/resolve")
        ->assertStatus(410)
        ->assertJsonPath('data.shortcutId', $shortcut->id);
});

// ---------------------------------------------------------------------------
//  Broken-link cleanup — DELETE /shortcuts/{shortcut}
// ---------------------------------------------------------------------------

test('a broken shortcut can be removed by its owner', function () {
    $folder = shortcutFolder('Vanished');
    $shortcut = DocumentNode::query()->create([
        'name' => 'Vanished',
        'kind' => DocumentNode::KIND_SHORTCUT,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'target_id' => $folder->id,
        'owner_id' => $this->user->id,
    ]);
    $folder->delete();

    $this->withToken($this->token)
        ->deleteJson("/api/document-manager/shortcuts/{$shortcut->id}")
        ->assertOk();

    expect(DocumentNode::query()->whereKey($shortcut->id)->exists())->toBeFalse();
});

test('an intact shortcut cannot be removed via the broken-link endpoint', function () {
    $folder = shortcutFolder('Intact');
    $shortcut = DocumentNode::query()->create([
        'name' => 'Intact',
        'kind' => DocumentNode::KIND_SHORTCUT,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'target_id' => $folder->id,
        'owner_id' => $this->user->id,
    ]);

    $this->withToken($this->token)
        ->deleteJson("/api/document-manager/shortcuts/{$shortcut->id}")
        ->assertStatus(422);

    expect(DocumentNode::query()->whereKey($shortcut->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
//  Rename guard exemption
// ---------------------------------------------------------------------------

test('several shortcuts to one target may share its name in a folder', function () {
    $destination = shortcutFolder('destination');
    $target = shortcutFolder('Reports');
    $first = DocumentNode::query()->create([
        'name' => 'Reports',
        'kind' => DocumentNode::KIND_SHORTCUT,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $destination->id,
        'target_id' => $target->id,
        'owner_id' => $this->user->id,
    ]);

    // Second shortcut with the same display name — allowed for shortcuts.
    $second = DocumentNode::query()->create([
        'name' => 'Reports',
        'kind' => DocumentNode::KIND_SHORTCUT,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $destination->id,
        'target_id' => $target->id,
        'owner_id' => $this->user->id,
    ]);

    // Renaming the first keeps working (same name → guard exempt).
    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$first->id}", ['name' => 'Reports'])
        ->assertOk();

    expect($second->fresh()->name)->toBe('Reports')
        ->and($first->fresh()->name)->toBe('Reports');
});

test('browsing a folder lists shortcuts alongside folders and files', function () {
    $folder = shortcutFolder('root');
    $target = shortcutFolder('Targeted', $folder->id);
    DocumentNode::query()->create([
        'name' => 'Targeted',
        'kind' => DocumentNode::KIND_SHORTCUT,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $folder->id,
        'target_id' => $target->id,
        'owner_id' => $this->user->id,
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/document-manager/browse?folderId={$folder->id}")
        ->assertOk();

    // Shortcuts live in their own browse section, carrying the target shape.
    $shortcuts = collect($response->json('data.shortcuts'));
    expect($shortcuts)->toHaveCount(1)
        ->and($shortcuts[0]['kind'])->toBe('shortcut')
        ->and($shortcuts[0]['target']['id'])->toBe($target->id)
        ->and($shortcuts[0]['target']['name'])->toBe('Targeted');
});
