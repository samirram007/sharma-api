<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\DocumentManager\Models\DocumentNodeShare;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Feature tests for per-share action permissions:
 *  - syncShares stores per-target permission grants (default view-only).
 *  - A view-only share may NOT move/copy/delete the owner's node (403).
 *  - Grants unlock the matching action (copy with 'copy', delete with
 *    'delete', move/rename with 'write').
 *  - The share payload round-trips through nodeShape (sharedWith[].permissions).
 *
 * Same request pattern as the other DocumentManager tests: real jwt.cookies
 * middleware with a JWT minted for a freshly created user.
 */
beforeEach(function () {
    Notification::fake();
    Storage::fake('local');

    $this->owner = User::create([
        'name' => 'Share Owner',
        'email' => 'share-owner@example.com',
        'password' => 'password',
    ]);
    $this->collaborator = User::create([
        'name' => 'Collaborator',
        'email' => 'collaborator@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->owner);
});

function shareNode(DocumentNode $node, int $userId, array $permissions): void
{
    // updateOrCreate keeps direct shareNode() calls idempotent when a test
    // first shares via the API (syncShares) then adjusts grants directly.
    DocumentNodeShare::query()->updateOrCreate([
        'document_node_id' => $node->id,
        'target_type' => 'user',
        'target_id' => $userId,
    ], [
        'permissions' => DocumentNodeShare::normalizePermissions($permissions),
        'shared_by' => $node->owner_id,
    ]);
    $node->update(['visibility' => DocumentNode::VIS_PROTECTED]);
}

function sharedFolderFor(User $owner, string $name): DocumentNode
{
    return DocumentNode::query()->create([
        'name' => $name,
        'kind' => DocumentNode::KIND_FOLDER,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => null,
        'owner_id' => $owner->id,
    ]);
}

// ---------------------------------------------------------------------------
//  syncShares — permission storage
// ---------------------------------------------------------------------------

test('syncShares defaults to view-only when no permissions are sent', function () {
    $folder = sharedFolderFor($this->owner, 'default perms');

    $this->withToken($this->token)
        ->putJson("/api/document-manager/nodes/{$folder->id}/shares", [
            'userIds' => [$this->collaborator->id],
        ])
        ->assertOk();

    $share = DocumentNodeShare::query()
        ->where('document_node_id', $folder->id)
        ->where('target_id', $this->collaborator->id)
        ->first();

    expect($share)->not->toBeNull()
        ->and($share->grants(DocumentNodeShare::PERM_VIEW))->toBeTrue()
        ->and($share->grants(DocumentNodeShare::PERM_WRITE))->toBeFalse()
        ->and($share->grants(DocumentNodeShare::PERM_DELETE))->toBeFalse();
});

test('syncShares stores per-target action grants and exposes them in sharedWith', function () {
    $folder = sharedFolderFor($this->owner, 'granted perms');

    $this->withToken($this->token)
        ->putJson("/api/document-manager/nodes/{$folder->id}/shares", [
            'userIds' => [$this->collaborator->id],
            'permissions' => [
                "user:{$this->collaborator->id}" => ['view', 'write', 'copy'],
            ],
        ])
        ->assertOk();

    $share = DocumentNodeShare::query()
        ->where('document_node_id', $folder->id)
        ->where('target_id', $this->collaborator->id)
        ->first();

    expect($share->grants(DocumentNodeShare::PERM_WRITE))->toBeTrue()
        ->and($share->grants(DocumentNodeShare::PERM_COPY))->toBeTrue()
        ->and($share->grants(DocumentNodeShare::PERM_DELETE))->toBeFalse()
        ->and($share->grantedPermissions())->toContain('view');

    // Round-trip through the node shape.
    $this->withToken($this->token)
        ->get('/api/document-manager/shared-by-me')
        ->assertOk()
        ->assertJsonPath('data.folders.0.sharedWith.0.permissions.1', 'write');
});

// ---------------------------------------------------------------------------
//  Enforcement — move / copy / delete under share permissions
// ---------------------------------------------------------------------------

test('view-only share cannot move a shared folder (no write grant)', function () {
    $folder = sharedFolderFor($this->owner, 'locked');
    $destination = sharedFolderFor($this->owner, 'destination');
    shareNode($folder, $this->collaborator->id, ['view']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->patchJson("/api/document-manager/nodes/{$folder->id}", [
            'parentId' => $destination->id,
        ])
        ->assertStatus(403);

    expect($folder->fresh()->parent_id)->toBeNull();
});

test('write grant allows moving a shared folder', function () {
    $folder = sharedFolderFor($this->owner, 'movable');
    $destination = sharedFolderFor($this->owner, 'destination');
    shareNode($folder, $this->collaborator->id, ['view', 'write']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->patchJson("/api/document-manager/nodes/{$folder->id}", [
            'parentId' => $destination->id,
        ])
        ->assertOk();

    expect($folder->fresh()->parent_id)->toBe($destination->id);
});

test('view-only share cannot copy, copy grant allows it', function () {
    $folder = sharedFolderFor($this->owner, 'copyable');
    shareNode($folder, $this->collaborator->id, ['view']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->postJson("/api/document-manager/nodes/{$folder->id}/copy", ['parentId' => null])
        ->assertStatus(403);

    // Grant copy — now it succeeds and the copy belongs to the collaborator.
    shareNode($folder, $this->collaborator->id, ['view', 'copy']);
    $this->withToken($collaboratorToken)
        ->postJson("/api/document-manager/nodes/{$folder->id}/copy", ['parentId' => null])
        ->assertCreated();

    expect(DocumentNode::query()->where('name', 'copyable (2)')->exists())->toBeTrue();
});

test('view-only share cannot delete, delete grant allows it', function () {
    $folder = sharedFolderFor($this->owner, 'deletable');
    shareNode($folder, $this->collaborator->id, ['view']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->deleteJson("/api/document-manager/nodes/{$folder->id}")
        ->assertStatus(403);
    expect($folder->fresh())->not->toBeNull();

    shareNode($folder, $this->collaborator->id, ['view', 'delete']);
    $this->withToken($collaboratorToken)
        ->deleteJson("/api/document-manager/nodes/{$folder->id}")
        ->assertOk();
    expect($folder->fresh())->toBeNull();
});

// ---------------------------------------------------------------------------
//  Write grant — create/upload inside folders shared with the user
// ---------------------------------------------------------------------------

test('view-only share cannot create a folder inside a shared folder', function () {
    $folder = sharedFolderFor($this->owner, 'readonly');
    shareNode($folder, $this->collaborator->id, ['view']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->postJson('/api/document-manager/folders', [
            'name' => 'sneaky',
            'parentId' => $folder->id,
        ])
        ->assertStatus(403);

    expect(DocumentNode::query()->where('name', 'sneaky')->exists())->toBeFalse();
});

test('write grant allows creating a folder inside a shared folder', function () {
    $folder = sharedFolderFor($this->owner, 'writable');
    shareNode($folder, $this->collaborator->id, ['view', 'write']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->postJson('/api/document-manager/folders', [
            'name' => 'welcome',
            'parentId' => $folder->id,
        ])
        ->assertCreated();

    $created = DocumentNode::query()->where('name', 'welcome')->first();
    expect($created)->not->toBeNull()
        ->and($created->parent_id)->toBe($folder->id)
        // The creator owns what they add — not the folder's owner.
        ->and($created->owner_id)->toBe($this->collaborator->id);
});

test('write grant allows uploading a file into a shared folder', function () {
    $folder = sharedFolderFor($this->owner, 'uploadable');
    shareNode($folder, $this->collaborator->id, ['view', 'write']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->postJson('/api/document-manager/upload', [
            'file' => UploadedFile::fake()->create('notes.txt', 12),
            'parentId' => $folder->id,
        ])
        ->assertCreated();

    $file = DocumentNode::query()->where('name', 'notes.txt')->first();
    expect($file)->not->toBeNull()
        ->and($file->kind)->toBe(DocumentNode::KIND_FILE)
        ->and($file->owner_id)->toBe($this->collaborator->id);
});

// The inheritance rule: a write grant on a shared root also covers its
// subfolders — the nearest shared ancestor's grants decide.
test('write grant on a shared folder covers its subfolders', function () {
    $root = sharedFolderFor($this->owner, 'root-tree');
    $sub = DocumentNode::query()->create([
        'name' => 'sub',
        'kind' => DocumentNode::KIND_FOLDER,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $root->id,
        'owner_id' => $this->owner->id,
    ]);
    shareNode($root, $this->collaborator->id, ['view', 'write']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->postJson('/api/document-manager/folders', [
            'name' => 'deep',
            'parentId' => $sub->id,
        ])
        ->assertCreated();

    $deep = DocumentNode::query()->where('name', 'deep')->first();
    expect($deep)->not->toBeNull()
        ->and($deep->parent_id)->toBe($sub->id);
});

test('view-only grant on a shared root blocks writes inside its subfolders', function () {
    $root = sharedFolderFor($this->owner, 'locked-tree');
    $sub = DocumentNode::query()->create([
        'name' => 'sub-locked',
        'kind' => DocumentNode::KIND_FOLDER,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $root->id,
        'owner_id' => $this->owner->id,
    ]);
    shareNode($root, $this->collaborator->id, ['view']);

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->postJson('/api/document-manager/folders', [
            'name' => 'deep-locked',
            'parentId' => $sub->id,
        ])
        ->assertStatus(403);

    expect(DocumentNode::query()->where('name', 'deep-locked')->exists())->toBeFalse();
});

test('an unrelated user without any share cannot write inside a shared folder', function () {
    $folder = sharedFolderFor($this->owner, 'stranger-zone');
    shareNode($folder, $this->collaborator->id, ['view', 'write']);

    $stranger = User::create([
        'name' => 'Stranger Two',
        'email' => 'stranger-two@example.com',
        'password' => 'password',
    ]);
    $strangerToken = JWTAuth::fromUser($stranger);

    $this->withToken($strangerToken)
        ->postJson('/api/document-manager/folders', [
            'name' => 'intrusion',
            'parentId' => $folder->id,
        ])
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
//  Developer share restriction — only admin/super admin/developer may share
//  WITH developer accounts or the DEVELOPER role
// ---------------------------------------------------------------------------

function giveRole(int $userId, string $code): void
{
    // RefreshDatabase runs no seeders, so create the role on demand
    // (same stable ids as RoleSeeder).
    $role = Role::query()->firstOrCreate(
        ['code' => $code],
        ['name' => ucfirst(strtolower(str_replace('_', ' ', $code))), 'status' => 'active']
    );
    DB::table('user_roles')->insertOrIgnore([
        'user_id' => $userId,
        'role_id' => $role->id,
    ]);
}

function developerRole(): Role
{
    return Role::query()->firstOrCreate(
        ['code' => 'DEVELOPER'],
        ['name' => 'Developer', 'status' => 'active']
    );
}

test('regular user share targets exclude developer accounts and the DEVELOPER role', function () {
    $developerRole = developerRole();
    giveRole($this->collaborator->id, 'EMPLOYEE');

    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $targets = $this->withToken($collaboratorToken)
        ->getJson('/api/document-manager/share-targets')
        ->assertOk()
        ->json('data');

    // Developers/DEVELOPER role are hidden for non-privileged users.
    expect(collect($targets['roles'])->pluck('id')->all())->not->toContain($developerRole->id);
    expect(collect($targets['users'])->values())->each->toBeArray();
});

test('developer user sees developer accounts and the DEVELOPER role in share targets', function () {
    giveRole($this->owner->id, 'DEVELOPER');

    $targets = $this->withToken($this->token)
        ->getJson('/api/document-manager/share-targets')
        ->assertOk()
        ->json('data');

    expect(collect($targets['roles'])->pluck('id')->all())->toContain(developerRole()->id);
});

test('admin sees the DEVELOPER role in share targets', function () {
    // Create the DEVELOPER role BEFORE the request — otherwise the picker
    // legitimately has nothing to show.
    $developerRole = developerRole();
    giveRole($this->owner->id, 'ADMIN');

    $targets = $this->withToken($this->token)
        ->getJson('/api/document-manager/share-targets')
        ->assertOk()
        ->json('data');

    expect(collect($targets['roles'])->pluck('id')->all())->toContain($developerRole->id);
});

test('regular user cannot share with a developer account even via crafted request', function () {
    $developer = User::create([
        'name' => 'Crafted Dev',
        'email' => 'crafted-dev@example.com',
        'password' => 'password',
    ]);
    giveRole($developer->id, 'DEVELOPER');

    // The folder belongs to the collaborator (a plain employee) so the share
    // request itself is authorized — only the developer target is illegitimate.
    $folder = sharedFolderFor($this->collaborator, 'dev-locked');

    // The collaborator (regular employee) hand-crafts a share including the
    // developer — the server must strip it instead of persisting it.
    $collaboratorToken = JWTAuth::fromUser($this->collaborator);
    $this->withToken($collaboratorToken)
        ->putJson("/api/document-manager/nodes/{$folder->id}/shares", [
            'userIds' => [$developer->id],
        ])
        ->assertOk();

    $exists = DocumentNodeShare::query()
        ->where('document_node_id', $folder->id)
        ->where('target_type', 'user')
        ->where('target_id', $developer->id)
        ->exists();
    expect($exists)->toBeFalse();
});

test('developer user can share with another developer account', function () {
    giveRole($this->owner->id, 'DEVELOPER');
    $developer = User::create([
        'name' => 'Second Dev',
        'email' => 'second-dev@example.com',
        'password' => 'password',
    ]);
    giveRole($developer->id, 'DEVELOPER');

    $folder = sharedFolderFor($this->owner, 'dev-share-ok');
    $this->withToken($this->token)
        ->putJson("/api/document-manager/nodes/{$folder->id}/shares", [
            'userIds' => [$developer->id],
        ])
        ->assertOk();

    $exists = DocumentNodeShare::query()
        ->where('document_node_id', $folder->id)
        ->where('target_type', 'user')
        ->where('target_id', $developer->id)
        ->exists();
    expect($exists)->toBeTrue();
});

test('an unshared user can never act on a protected node even with grants requested', function () {
    $folder = sharedFolderFor($this->owner, 'stranger');
    shareNode($folder, $this->collaborator->id, ['view', 'delete']);

    $stranger = User::create([
        'name' => 'Stranger',
        'email' => 'stranger@example.com',
        'password' => 'password',
    ]);
    $strangerToken = JWTAuth::fromUser($stranger);

    // The stranger holds no share of this node — the response must not leak
    // anything about it, so it reads as a permission refusal either way.
    $this->withToken($strangerToken)
        ->deleteJson("/api/document-manager/nodes/{$folder->id}")
        ->assertStatus(403);
});
