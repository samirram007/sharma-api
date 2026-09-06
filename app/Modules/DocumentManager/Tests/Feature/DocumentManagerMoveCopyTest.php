<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Feature tests for DocumentManager move / copy / delete semantics.
 *
 * Exercises the exact contract the frontend relies on:
 *  - PATCH (not PUT) /api/document-manager/nodes/{node} moves nodes;
 *    moving a folder into its own subtree must fail with 422.
 *  - POST /api/document-manager/nodes/{node}/copy deep-copies folders,
 *    copies stored file payloads, and uniquifies sibling names.
 *  - DELETE removes folder subtrees and their physical files.
 *
 * Every request goes through the real jwt.cookies middleware with a JWT
 * minted for a freshly created user (same pattern as RolePermissionTest).
 */
beforeEach(function () {
    Storage::fake('local');

    $this->user = User::create([
        'name' => 'Doc Tree Tester',
        'email' => 'doc-tree-tester@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);
});

function createFolder(string $name, ?int $parentId = null): DocumentNode
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
//  Move — PATCH nodes/{node} with parent_id
// ---------------------------------------------------------------------------

test('PATCH reparents a sibling folder under another folder', function () {
    $parent = createFolder('parent');
    $sibling = createFolder('first-child');

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$sibling->id}", ['parentId' => $parent->id])
        ->assertOk();

    expect($sibling->fresh()->parent_id)->toBe($parent->id);
});

test('PATCH with null parentId moves a node back to the root', function () {
    $parent = createFolder('parent');
    $child = createFolder('child', $parent->id);

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$child->id}", ['parentId' => null])
        ->assertOk();

    expect($child->fresh()->parent_id)->toBeNull();
});

test('moving a folder into its own descendant fails with 422', function () {
    $root = createFolder('root');
    $child = createFolder('child', $root->id);
    $grandchild = createFolder('grandchild', $child->id);

    $this->withToken($this->token)
        ->patchJson("/api/document-manager/nodes/{$root->id}", ['parentId' => $grandchild->id])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'A folder cannot be moved into itself.']);

    expect($root->fresh()->parent_id)->toBeNull();
});

test('PUT is not a supported method on nodes', function () {
    $folder = createFolder('put-target');

    $this->withToken($this->token)
        ->putJson("/api/document-manager/nodes/{$folder->id}", ['parentId' => null])
        ->assertStatus(405);
});

// ---------------------------------------------------------------------------
//  Copy — POST nodes/{node}/copy
// ---------------------------------------------------------------------------

test('copy duplicates a file and its stored payload into the destination', function () {
    $parent = createFolder('parent');
    $destination = createFolder('destination');
    UploadedFile::fake()->createWithContent('report.txt', 'hello copy')->storeAs(
        'documents', 'tests/source.txt'
    );
    $file = DocumentNode::query()->create([
        'name' => 'report.txt',
        'kind' => DocumentNode::KIND_FILE,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $parent->id,
        'owner_id' => $this->user->id,
        'mime_type' => 'text/plain',
        'extension' => 'txt',
        'storage_path' => 'documents/tests/source.txt',
        'size_bytes' => 10,
    ]);

    $response = $this->withToken($this->token)
        ->postJson("/api/document-manager/nodes/{$file->id}/copy", ['parentId' => $destination->id])
        ->assertCreated();

    $copyId = $response->json('data.id');
    $copy = DocumentNode::query()->find($copyId);

    expect($copy->parent_id)->toBe($destination->id)
        ->and($copy->name)->toBe('report.txt')
        ->and($copy->storage_path)->not->toBe($file->storage_path)
        ->and(Storage::disk('local')->exists($copy->storage_path))->toBeTrue()
        ->and(Storage::disk('local')->get($copy->storage_path))->toBe('hello copy');
});

test('copy of a folder duplicates the whole subtree', function () {
    $root = createFolder('root');
    $child = createFolder('child', $root->id);
    $grandchild = createFolder('grandchild', $child->id);

    $response = $this->withToken($this->token)
        ->postJson("/api/document-manager/nodes/{$root->id}/copy", ['parentId' => null])
        ->assertCreated();

    $copyId = $response->json('data.id');
    $copy = DocumentNode::query()->find($copyId);
    $copyChild = DocumentNode::query()->where('parent_id', $copyId)->first();
    $copyGrandchild = DocumentNode::query()->where('parent_id', $copyChild->id)->first();

    expect($copy->name)->toBe('root (2)')
        ->and($copyChild->name)->toBe('child')
        ->and($copyGrandchild->name)->toBe('grandchild')
        // Original subtree untouched.
        ->and(DocumentNode::query()->find($child->id)->parent_id)->toBe($root->id);
});

test('copy uniquifies the name among destination siblings', function () {
    $destination = createFolder('destination');
    createFolder('report', $destination->id);

    $source = createFolder('report');

    $response = $this->withToken($this->token)
        ->postJson("/api/document-manager/nodes/{$source->id}/copy", ['parentId' => $destination->id])
        ->assertCreated();

    expect($response->json('data.name'))->toBe('report (2)');
});

test('copy of a folder into its own descendant fails with 422', function () {
    $root = createFolder('root');
    $child = createFolder('child', $root->id);

    $this->withToken($this->token)
        ->postJson("/api/document-manager/nodes/{$root->id}/copy", ['parentId' => $child->id])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'A folder cannot be copied into itself.']);
});

// ---------------------------------------------------------------------------
//  Folders list — GET /document-manager/folders
// ---------------------------------------------------------------------------

test('folders endpoint returns accessible folders as id/name/parentId', function () {
    $root = createFolder('visible-root');
    createFolder('hidden-file-parent');

    $response = $this->withToken($this->token)
        ->getJson('/api/document-manager/folders')
        ->assertOk();

    $items = collect($response->json('data'));
    $row = $items->firstWhere('id', $root->id);

    expect($row['name'])->toBe('visible-root')
        ->and($row['parentId'])->toBeNull();
});

// ---------------------------------------------------------------------------
//  Delete — DELETE nodes/{node}
// ---------------------------------------------------------------------------

test('delete removes a folder subtree and the stored files', function () {
    $root = createFolder('root');
    $child = createFolder('child', $root->id);
    UploadedFile::fake()->createWithContent('x', 'payload')->storeAs('documents', 'tests/deleteme.txt');
    $file = DocumentNode::query()->create([
        'name' => 'note.txt',
        'kind' => DocumentNode::KIND_FILE,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => $child->id,
        'owner_id' => $this->user->id,
        'storage_path' => 'documents/tests/deleteme.txt',
        'size_bytes' => 7,
    ]);

    $this->withToken($this->token)
        ->deleteJson("/api/document-manager/nodes/{$root->id}")
        ->assertOk();

    expect(DocumentNode::query()->whereKey($root->id)->exists())->toBeFalse()
        ->and(DocumentNode::query()->whereKey($child->id)->exists())->toBeFalse()
        ->and(DocumentNode::query()->whereKey($file->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists('documents/tests/deleteme.txt'))->toBeFalse();
});
