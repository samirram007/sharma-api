<?php

namespace Modules\DocumentManager\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\DocumentManager\Models\DocumentNode;

/**
 * A node was created inside a folder (folder created, file uploaded,
 * shortcut added). Broadcast on the parent folder's private channel so
 * every user with access to that folder — the owner ("host") and share
 * recipients ("clients") alike — sees the change in real time without
 * refreshing. Channel authorization lives in routes/channels.php and
 * reuses DocumentNode::userCanAccessFolder().
 */
class DocumentNodeChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DocumentNode $node)
    {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        // Root-level creations (parent_id null) have no shared folder
        // audience — only the actor's own view, which updates via its
        // mutation — so nothing to broadcast.
        if ($this->node->parent_id === null) {
            return [];
        }

        return [
            new PrivateChannel('document.folder.'.$this->node->parent_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'document.node.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'action' => 'created',
            'nodeId' => $this->node->id,
            'parentId' => $this->node->parent_id,
            'kind' => $this->node->kind,
            'name' => $this->node->name,
            'actorId' => $this->node->owner_id,
        ];
    }
}
