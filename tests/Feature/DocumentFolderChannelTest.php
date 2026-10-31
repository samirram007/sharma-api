<?php

use Illuminate\Support\Facades\DB;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\DocumentManager\Models\DocumentNodeShare;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/*
| The folder realtime channel must authorize everyone who can *browse* the
| folder: its owner, users it is shared with directly or through a role, and
| anyone reaching it by inheritance from a shared ancestor folder.
*/

beforeEach(function () {
    // phpunit.xml pins BROADCAST_CONNECTION=null and the null broadcaster
    // skips channel authorization altogether (every request answers 200), so
    // these tests need a real pusher driver and the channel callbacks
    // registered on the broadcaster the requests resolve.
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => 'test-app',
    ]);

    require base_path('routes/channels.php');
});

function folderChannelUser(string $email, string $roleCode = 'STAFF'): User
{
    $user = User::create(['name' => $email, 'email' => $email, 'password' => 'password']);

    $roleId = DB::table('roles')->where('code', $roleCode)->value('id')
        ?? DB::table('roles')->insertGetId([
            'name' => ucfirst(strtolower($roleCode)),
            'code' => $roleCode,
            'status' => 'active',
        ]);

    DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $roleId]);

    return $user;
}

function folderChannelFolder(User $owner, ?DocumentNode $parent = null, string $visibility = DocumentNode::VIS_PRIVATE): DocumentNode
{
    return DocumentNode::create([
        'owner_id' => $owner->id,
        'parent_id' => $parent?->id,
        'kind' => DocumentNode::KIND_FOLDER,
        'name' => 'folder-'.uniqid(),
        'visibility' => $visibility,
    ]);
}

function folderChannelShare(DocumentNode $node, string $targetType, int $targetId, User $by): void
{
    DocumentNodeShare::create([
        'document_node_id' => $node->id,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'permissions' => DocumentNodeShare::normalizePermissions([DocumentNodeShare::PERM_VIEW]),
        'shared_by' => $by->id,
    ]);
    $node->update(['visibility' => DocumentNode::VIS_PROTECTED]);
}

function authorizeFolderChannel(User $user, int $folderId): int
{
    return test()->withToken(JWTAuth::fromUser($user))
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-document.folder.'.$folderId,
        ])
        ->getStatusCode();
}

it('authorizes the owner', function () {
    $owner = folderChannelUser('owner@example.com');
    $folder = folderChannelFolder($owner);

    expect(authorizeFolderChannel($owner, $folder->id))->toBe(200);
});

it('authorizes a user the folder is shared with directly', function () {
    $owner = folderChannelUser('owner@example.com');
    $viewer = folderChannelUser('viewer@example.com');
    $folder = folderChannelFolder($owner);
    folderChannelShare($folder, 'user', $viewer->id, $owner);

    expect(authorizeFolderChannel($viewer, $folder->id))->toBe(200);
});

it('authorizes a user the folder is shared with through a role', function () {
    $owner = folderChannelUser('owner@example.com');
    $viewer = folderChannelUser('viewer@example.com', 'DOC_VIEWERS');
    $folder = folderChannelFolder($owner);
    $roleId = (int) DB::table('roles')->where('code', 'DOC_VIEWERS')->value('id');
    folderChannelShare($folder, 'role', $roleId, $owner);

    expect(authorizeFolderChannel($viewer, $folder->id))->toBe(200);
});

it('authorizes a nested folder reached through a shared ancestor', function () {
    $owner = folderChannelUser('owner@example.com');
    $viewer = folderChannelUser('viewer@example.com');
    $root = folderChannelFolder($owner);
    $child = folderChannelFolder($owner, $root);
    $grandchild = folderChannelFolder($owner, $child);
    folderChannelShare($root, 'user', $viewer->id, $owner);

    expect(authorizeFolderChannel($viewer, $child->id))->toBe(200)
        ->and(authorizeFolderChannel($viewer, $grandchild->id))->toBe(200);
});

it('authorizes a company-public folder', function () {
    $owner = folderChannelUser('owner@example.com');
    $viewer = folderChannelUser('viewer@example.com');
    $folder = folderChannelFolder($owner, null, DocumentNode::VIS_PUBLIC);

    expect(authorizeFolderChannel($viewer, $folder->id))->toBe(200);
});

it('denies a user with no share', function () {
    $owner = folderChannelUser('owner@example.com');
    $stranger = folderChannelUser('stranger@example.com');
    $folder = folderChannelFolder($owner);

    expect(authorizeFolderChannel($stranger, $folder->id))->toBe(403);
});

it('denies a shared file whose parent folder is not shared', function () {
    $owner = folderChannelUser('owner@example.com');
    $viewer = folderChannelUser('viewer@example.com');
    $folder = folderChannelFolder($owner);
    $file = DocumentNode::create([
        'owner_id' => $owner->id,
        'parent_id' => $folder->id,
        'kind' => DocumentNode::KIND_FILE,
        'name' => 'shared.pdf',
        'visibility' => DocumentNode::VIS_PRIVATE,
    ]);
    folderChannelShare($file, 'user', $viewer->id, $owner);

    // The viewer may open the file itself, but the folder channel stays closed.
    expect(authorizeFolderChannel($viewer, $folder->id))->toBe(403);
});
