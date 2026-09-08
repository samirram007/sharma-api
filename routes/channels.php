<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\User\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.notifications.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});

// Shared-folder realtime: every user who can browse the folder — the owner
// and share recipients alike — may listen, so co-sharers see each other's
// created/uploaded nodes without refreshing.
Broadcast::channel('document.folder.{folderId}', function (User $user, int $folderId) {
    return DocumentNode::userCanAccessFolder($user->id, $folderId);
});
