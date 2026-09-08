<?php

namespace Modules\DocumentManager\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\DocumentManager\Models\DocumentCategory;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\DocumentManager\Models\DocumentNodeShare;
use Modules\DocumentManager\Events\DocumentNodeChanged;
use Modules\DocumentManager\Models\DocumentType;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use RuntimeException;

class DocumentManagerService
{
    /** Default seed taxonomy used when the module is first exercised. */
    public function seedTaxonomyIfEmpty(): void
    {
        if (DocumentCategory::query()->exists() || DocumentType::query()->exists()) {
            return;
        }

        $categories = [
            ['name' => 'General', 'slug' => 'general', 'color' => '#64748b'],
            ['name' => 'Finance', 'slug' => 'finance', 'color' => '#16a34a'],
            ['name' => 'Legal', 'slug' => 'legal', 'color' => '#dc2626'],
            ['name' => 'Human Resources', 'slug' => 'human-resources', 'color' => '#2563eb'],
            ['name' => 'Operations', 'slug' => 'operations', 'color' => '#d97706'],
            ['name' => 'Customers', 'slug' => 'customers', 'color' => '#7c3aed'],
            ['name' => 'Suppliers', 'slug' => 'suppliers', 'color' => '#0891b2'],
        ];
        foreach ($categories as $category) {
            DocumentCategory::query()->firstOrCreate(['slug' => $category['slug']], $category);
        }

        $types = [
            ['name' => 'PDF', 'slug' => 'pdf', 'mime_category' => 'pdf'],
            ['name' => 'Word', 'slug' => 'word', 'mime_category' => 'docx'],
            ['name' => 'Spreadsheet', 'slug' => 'spreadsheet', 'mime_category' => 'sheet'],
            ['name' => 'Presentation', 'slug' => 'presentation', 'mime_category' => 'slide'],
            ['name' => 'Image', 'slug' => 'image', 'mime_category' => 'image'],
            ['name' => 'Text', 'slug' => 'text', 'mime_category' => 'text'],
            ['name' => 'Archive', 'slug' => 'archive', 'mime_category' => 'archive'],
            ['name' => 'Other', 'slug' => 'other', 'mime_category' => 'other'],
        ];
        foreach ($types as $type) {
            DocumentType::query()->firstOrCreate(['slug' => $type['slug']], $type);
        }
    }

    /** List the accessible children of a folder (or roots when folderId is null). */
    public function browse(?int $folderId): array
    {
        $this->seedTaxonomyIfEmpty();

        $query = DocumentNode::query()
            ->with(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares', 'target:id,kind,name,target_id,extension,mime_type,size_bytes'])
            ->accessible()
            ->where('parent_id', $folderId);

        $folders = (clone $query)->where('kind', DocumentNode::KIND_FOLDER)->orderBy('name')->get();
        $files = (clone $query)->where('kind', DocumentNode::KIND_FILE)->orderBy('name')->get();
        $shortcuts = (clone $query)->where('kind', DocumentNode::KIND_SHORTCUT)->orderBy('name')->get();

        $breadcrumb = $this->breadcrumb($folderId);

        return [
            'folderId' => $folderId,
            'breadcrumb' => $breadcrumb,
            'folders' => $folders,
            'files' => $files,
            'shortcuts' => $shortcuts,
        ];
    }

    /** Name/description search across every node the current user can access. */
    public function search(string $term, ?int $limit = 50): array
    {
        $this->seedTaxonomyIfEmpty();

        $term = trim($term);
        if ($term === '') {
            return [];
        }

        return DocumentNode::query()
            ->with(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares', 'target:id,kind,name,target_id,extension,mime_type,size_bytes'])
            ->accessible()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            })
            ->orderByDesc('updated_at')
            ->limit($limit ?? 50)
            ->get()
            ->all();
    }

    /**
     * Flat list of nodes other people shared with the current user —
     * accessible nodes this user does not own (shared directly, via role,
     * or company-public). Folders first, newest edits first.
     */
    public function sharedWithMe(): array
    {
        $this->seedTaxonomyIfEmpty();

        $query = DocumentNode::query()
            ->with(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares', 'target:id,kind,name,target_id,extension,mime_type,size_bytes'])
            ->accessible()
            ->where(function (Builder $query) {
                $query->where('owner_id', '!=', Auth::id())
                    ->orWhereNull('owner_id');
            })
            ->orderByDesc('updated_at');

        // Shares now inherit down the tree, so without this filter the list
        // would flood with every descendant of a shared folder. Surface the
        // share ENTRY POINTS only: hide nodes whose parent is a foreign-owned
        // folder the user can reach — they open via their shared ancestor.
        $sharedFolderIds = DocumentNode::query()
            ->accessible()
            ->where('kind', DocumentNode::KIND_FOLDER)
            ->where('owner_id', '!=', Auth::id())
            ->pluck('id');
        if ($sharedFolderIds->isNotEmpty()) {
            $query->where(function (Builder $q) use ($sharedFolderIds) {
                $q->whereNull('parent_id')->orWhereNotIn('parent_id', $sharedFolderIds);
            });
        }

        $query = $query->limit(200)->get();

        return [
            'folders' => $query->where('kind', DocumentNode::KIND_FOLDER)->values()->all(),
            'files' => $query->where('kind', DocumentNode::KIND_FILE)->values()->all(),
            'shortcuts' => $query->where('kind', DocumentNode::KIND_SHORTCUT)->values()->all(),
        ];
    }

    /**
     * Flat list of the current user's own nodes that are shared outward —
     * protected nodes with at least one share, or company-public ones.
     */
    public function sharedByMe(): array
    {
        $this->seedTaxonomyIfEmpty();

        $query = DocumentNode::query()
            ->with(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares', 'target:id,kind,name,target_id,extension,mime_type,size_bytes'])
            ->where('owner_id', Auth::id())
            ->where(function (Builder $query) {
                $query->where('visibility', DocumentNode::VIS_PUBLIC)
                    ->orWhereHas('shares');
            })
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        return [
            'folders' => $query->where('kind', DocumentNode::KIND_FOLDER)->values()->all(),
            'files' => $query->where('kind', DocumentNode::KIND_FILE)->values()->all(),
            'shortcuts' => $query->where('kind', DocumentNode::KIND_SHORTCUT)->values()->all(),
        ];
    }

    /** Parent chain from the root down to (and including) folderId. */
    public function breadcrumb(?int $folderId): array
    {
        if (! $folderId) {
            return [];
        }
        $chain = [];
        $current = DocumentNode::query()->find($folderId);
        while ($current && $current->kind === DocumentNode::KIND_FOLDER) {
            $chain[] = $current;
            $current = $current->parent;
        }

        return array_reverse($chain);
    }

    public function createFolder(array $data): DocumentNode
    {
        $this->assertFolderWriteAccess($data['parent_id'] ?? null);

        $node = DocumentNode::query()->create([
            'name' => trim($data['name']),
            'kind' => DocumentNode::KIND_FOLDER,
            'visibility' => $data['visibility'] ?? DocumentNode::VIS_PRIVATE,
            'parent_id' => $data['parent_id'] ?? null,
            'owner_id' => Auth::id(),
            'category_id' => $data['category_id'] ?? null,
            'type_id' => $data['type_id'] ?? null,
            'description' => $data['description'] ?? null,
        ]);
        $this->broadcastNodeCreated($node);

        return $node;
    }

    /**
     * Create a shortcut (link) to another node inside the destination folder.
     * Shortcuts are owned by their creator, inherit no payload, and may live
     * in any folder the user can write. Cycle guard: a shortcut cannot point
     * at another shortcut (chain growth) — it always targets the real node.
     */
    public function createShortcut(int $targetId, ?int $parentId, ?string $name = null): DocumentNode
    {
        $target = $this->requireAccessible($targetId);
        abort_if($target->kind === DocumentNode::KIND_SHORTCUT, 422, 'Shortcuts must point at a folder or file.');
        $this->assertFolderWriteAccess($parentId);

        $label = trim($name ?? '') !== ''
            ? trim((string) $name)
            : $target->name;

        $node = DocumentNode::query()->create([
            'name' => $label,
            'kind' => DocumentNode::KIND_SHORTCUT,
            'visibility' => DocumentNode::VIS_PRIVATE,
            'parent_id' => $parentId,
            'target_id' => $target->id,
            'owner_id' => Auth::id(),
        ]);
        $this->broadcastNodeCreated($node);

        return $node;
    }

    public function upload(array $data): DocumentNode
    {
        /** @var UploadedFile $file */
        $file = $data['file'];
        $this->assertFolderWriteAccess($data['parent_id'] ?? null);

        $original = $file->getClientOriginalName();
        // Normalize BEFORE deriving the extension: for Windows-style
        // "photo.jpg (2)" names PHP's getClientOriginalExtension() would
        // report "jpg (2)", poisoning both the extension column and the
        // storage object name. The normalized display name carries the real
        // extension, with guessExtension() as fallback for dotless names.
        $displayName = $this->stripDuplicateCounter($data['name'] ?? $original);
        $extension = strtolower(
            (string) (pathinfo($displayName, PATHINFO_EXTENSION) ?: ($file->guessExtension() ?: 'bin'))
        );
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        // No silent overwrite: siblings keep unique names, so a re-upload of
        // the same file becomes "name (2).ext" instead of a confusing twin.
        $uniqueName = $this->uniqueSiblingName(
            $data['parent_id'] ?? null,
            $displayName
        );
        // Randomised object name keeps the storage layout flat and safe.
        $objectName = date('Y/m/d').'/'.Str::uuid().'.'.$extension;
        $path = $file->storeAs('documents', $objectName, 'local');
        if ($path === false) {
            throw new RuntimeException('Could not store the uploaded file.');
        }

        $node = DocumentNode::query()->create([
            'name' => $uniqueName,
            'kind' => DocumentNode::KIND_FILE,
            'visibility' => $data['visibility'] ?? DocumentNode::VIS_PRIVATE,
            'parent_id' => $data['parent_id'] ?? null,
            'owner_id' => Auth::id(),
            'category_id' => $data['category_id'] ?? null,
            'type_id' => $data['type_id'] ?? $this->typeIdFor($extension),
            'description' => $data['description'] ?? null,
            'mime_type' => $mime,
            'extension' => $extension,
            'storage_path' => $path,
            'size_bytes' => $file->getSize(),
        ]);
        $this->broadcastNodeCreated($node);

        return $node;
    }

    /**
     * Fan out a creation to everyone with access to the destination folder:
     * on a shared folder the owner and co-sharers all refresh their view
     * (browsers listen via Echo on the document.folder.{id} channel).
     */
    private function broadcastNodeCreated(DocumentNode $node): void
    {
        if ($node->parent_id !== null) {
            DocumentNodeChanged::dispatch($node);
        }
    }

    public function updateNode(int $id, array $data): DocumentNode
    {
        $node = $this->requireEditable($id);

        // Move support: reparent when parent_id is explicitly present. The
        // optional conflict strategy rides along on the same PATCH call.
        if (array_key_exists('parent_id', $data)) {
            $this->moveNode($node, $data['parent_id'], $data['conflict'] ?? 'error');
        }

        // Renaming must never silently overwrite a sibling — the frontend
        // pre-checks via /conflicts, the backend is the final guard. Auto-named
        // shortcuts are exempt: several shortcuts to one target may share the
        // target's name inside the same folder.
        if (array_key_exists('name', $data) && $data['name'] !== null && $node->kind !== DocumentNode::KIND_SHORTCUT) {
            $newName = trim((string) $data['name']);
            $conflict = DocumentNode::query()
                ->where('parent_id', $node->parent_id)
                ->where('name', $newName)
                ->whereKeyNot($node->id)
                ->exists();
            abort_if($conflict, 422, "An item named \"{$newName}\" already exists in this folder.");
        }

        $node->update([
            'name' => $data['name'] ?? $node->name,
            'visibility' => $data['visibility'] ?? $node->visibility,
            'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $node->category_id,
            'type_id' => array_key_exists('type_id', $data) ? $data['type_id'] : $node->type_id,
            'description' => array_key_exists('description', $data) ? $data['description'] : $node->description,
        ]);

        return $node->fresh(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares']);
    }

    /**
     * Reparent a node into another folder (null = root), rejecting cycles
     * and non-folders. On name collision the caller decides: 'replace'
     * deletes the destination sibling first, 'rename' moves the node under a
     * fresh "name (2)"-style variant, default (anything else) 422s so a
     * silent overwrite can never happen without the user choosing.
     */
    public function moveNode(DocumentNode $node, ?int $newParentId, string $conflict = 'error'): void
    {
        if ((int) $node->parent_id === (int) $newParentId && ($node->parent_id !== null || $newParentId === null)) {
            return; // already there
        }

        if ($newParentId !== null) {
            $target = DocumentNode::query()->find($newParentId);
            abort_unless($target, 404, 'Destination folder not found.');
            abort_if($target->kind !== DocumentNode::KIND_FOLDER, 422, 'Destination must be a folder.');

            // A folder cannot be moved into itself or one of its descendants.
            if ($node->kind === DocumentNode::KIND_FOLDER) {
                $cursor = $target;
                while ($cursor) {
                    abort_if($cursor->id === $node->id, 422, 'A folder cannot be moved into itself.');
                    $cursor = $cursor->parent;
                }
            }
        }

        if ($newParentId !== null) {
            $sibling = DocumentNode::query()
                ->where('parent_id', $newParentId)
                ->where('name', $node->name)
                ->whereKeyNot($node->id)
                ->first();

            if ($sibling) {
                if ($conflict === 'replace') {
                    $this->deleteNode($sibling->id);
                } elseif ($conflict === 'rename') {
                    // Pick the free name BEFORE reparenting — otherwise the
                    // moved node's own original name counts as "taken" and
                    // the counter skips a number ("report (3)" not "(2)").
                    $taken = DocumentNode::query()
                        ->where('parent_id', $newParentId)
                        ->whereKeyNot($node->id)
                        ->pluck('name')
                        ->all();
                    $newName = $this->firstFreeName($taken, $node->name);
                    $node->update(['parent_id' => $newParentId, 'name' => $newName]);

                    return;
                } else {
                    abort(422, "An item named \"{$node->name}\" already exists in that folder.");
                }
            }
        }

        $node->update(['parent_id' => $newParentId]);
    }

    /**
     * Which destination siblings already hold the given names. The frontend
     * calls this before a move/copy so it can offer Skip / Replace / Keep
     * both; the mutation then carries the user's chosen conflict strategy.
     * $excludeId removes the moved node itself from both lists, so checking
     * a rename-in-place (same parent) reports no self-conflict.
     */
    public function conflictsFor(array $ids, ?int $parentId, ?int $excludeId = null): array
    {
        if ($parentId === null || $ids === []) {
            return []; // root is a shared container — many roots may share a name
        }

        $parent = DocumentNode::query()->find($parentId);
        abort_unless($parent, 404, 'Destination folder not found.');
        abort_if($parent->kind !== DocumentNode::KIND_FOLDER, 422, 'Destination must be a folder.');

        $nodes = DocumentNode::query()
            ->with(['owner:id,name'])
            ->whereIn('id', array_map('intval', $ids))
            ->get();
        abort_unless($nodes->isNotEmpty(), 404, 'Documents not found.');

        $taken = DocumentNode::query()
            ->where('parent_id', $parentId)
            ->whereKeyNot((int) $excludeId)
            ->pluck('name')
            ->all();

        $conflicts = [];
        foreach ($nodes as $node) {
            $name = $node->name;
            if ($node->parent_id === $parentId) {
                continue; // "moving onto itself" is a no-op, not a conflict
            }
            if (in_array($name, $taken, true)) {
                $existing = DocumentNode::query()
                    ->with(['owner:id,name'])
                    ->where('parent_id', $parentId)
                    ->where('name', $name)
                    ->first();
                $conflicts[] = [
                    'movedId' => $node->id,
                    'movedName' => $node->name,
                    'movedKind' => $node->kind,
                    'movedSizeBytes' => $node->size_bytes,
                    'movedUpdatedAt' => optional($node->updated_at)->toISOString(),
                    'existingId' => $existing?->id,
                    'existingKind' => $existing?->kind,
                    'existingSizeBytes' => $existing?->size_bytes,
                    'existingUpdatedAt' => optional($existing?->updated_at)->toISOString(),
                    'existingOwnerName' => $existing?->owner?->name,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Copy a node into another folder (null = root). Folders are copied
     * recursively (including stored file payloads); the copy is owned by the
     * current user. On name collision the caller decides: 'rename' picks the
     * first free "name (2)"-style variant (default, unchanged behavior),
     * 'replace' deletes the destination sibling first.
     */
    public function copyNode(int $id, ?int $destinationParentId, string $conflict = 'rename'): DocumentNode
    {
        // Copying someone else's shared node needs the 'copy' grant; your own
        // nodes always pass (owner/admin inside requireWithPermission).
        $node = $this->requireWithPermission($id, DocumentNodeShare::PERM_COPY);
        $this->assertCopyDestination($node, $destinationParentId);

        if ($conflict === 'replace') {
            $this->deleteDestinationSibling($destinationParentId, $node->name);
        }

        $name = $conflict === 'replace'
            ? $node->name
            : $this->uniqueCopyName($destinationParentId, $node->name);

        return $this->copySubtree($node, $destinationParentId, $name);
    }

    /** Delete the sibling holding $name inside $parentId, subtree included. */
    private function deleteDestinationSibling(?int $parentId, string $name): void
    {
        if ($parentId === null) {
            // Root may hold several same-name nodes (different owners); an
            // unnamed "replace" at root is ambiguous — rename instead.
            return;
        }

        $sibling = DocumentNode::query()
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->first();
        if ($sibling) {
            $this->deleteNode($sibling->id);
        }
    }

    /** Destination checks shared by move/copy: exists, is a folder, writable, no cycles. */
    private function assertCopyDestination(DocumentNode $node, ?int $parentId): void
    {
        if ($parentId === null) {
            return; // root is a shared container
        }
        $target = DocumentNode::query()->find($parentId);
        abort_unless($target, 404, 'Destination folder not found.');
        abort_if($target->kind !== DocumentNode::KIND_FOLDER, 422, 'Destination must be a folder.');

        // A folder cannot be copied into itself or one of its descendants.
        if ($node->kind === DocumentNode::KIND_FOLDER) {
            $cursor = $target;
            while ($cursor) {
                abort_if($cursor->id === $node->id, 422, 'A folder cannot be copied into itself.');
                $cursor = $cursor->parent;
            }
        }

        $this->assertFolderWriteAccess($parentId);
    }

    /** First free name among the destination siblings: X, X (2), X (3)… */
    private function uniqueCopyName(?int $parentId, string $name): string
    {
        return $this->uniqueSiblingName($parentId, $name);
    }

    /**
     * First free name among the siblings of the given parent (null = root):
     * "X" if unused, otherwise "X (2)", "X (3)"… For files the counter is
     * inserted before the extension so "a.txt" becomes "a (2).txt".
     *
     * OS/browser duplicate-download artifacts are normalized away first, so
     * an incoming "a.txt (2)" (Windows puts the counter AFTER the extension)
     * never lands verbatim and never grows into "a (2).txt (2)" — it joins
     * the "a.txt" family instead.
     */
    private function uniqueSiblingName(?int $parentId, string $name): string
    {
        $taken = DocumentNode::query()
            ->where('parent_id', $parentId)
            ->pluck('name')
            ->all();

        return $this->firstFreeName($taken, $name);
    }

    /** Pure name-picking half of uniqueSiblingName — unit-testable, no I/O. */
    private function firstFreeName(array $taken, string $name): string
    {
        $name = $this->stripDuplicateCounter($name);

        if (! in_array($name, $taken, true)) {
            return $name;
        }

        // "report.txt" → stem "report" + ".txt"; dotless names keep whole.
        $dot = strrpos($name, '.');
        $stem = $dot === false || $dot === 0 ? $name : substr($name, 0, $dot);
        $suffix = $dot === false || $dot === 0 ? '' : substr($name, $dot);

        $counter = 2;
        while (in_array("{$stem} ({$counter}){$suffix}", $taken, true)) {
            $counter++;
        }

        return "{$stem} ({$counter}){$suffix}";
    }

    /**
     * Drop a trailing "(N)" counter — with optional space, e.g. Windows's
     * "photo.jpg (2)" and the compact "photo(2).jpg" — so the counter sits
     * before the extension. Handles chains like "photo.jpg (2) (3)". A name
     * reduced to nothing is returned untouched.
     */
    private function stripDuplicateCounter(string $name): string
    {
        $name = trim($name);
        while (preg_match('/\s*\(\d+\)$/', $name) === 1) {
            $stripped = trim((string) preg_replace('/\s*\(\d+\)$/', '', $name));
            if ($stripped === '' || $stripped === $name) {
                break;
            }
            $name = $stripped;
        }

        return $name;
    }

    /** Recursively clone a node (and its subtree) under the given parent. */
    private function copySubtree(DocumentNode $node, ?int $parentId, string $name): DocumentNode
    {
        $copy = $node->replicate(['created_by', 'updated_by']);
        $copy->name = $name;
        $copy->parent_id = $parentId;
        $copy->owner_id = Auth::id();

        if ($node->kind === DocumentNode::KIND_FILE && $node->storage_path) {
            $extension = pathinfo($node->storage_path, PATHINFO_EXTENSION) ?: 'bin';
            $objectName = date('Y/m/d').'/'.Str::uuid().'.'.$extension;
            $newPath = 'documents/'.$objectName;
            if (! Storage::disk('local')->copy($node->storage_path, $newPath)) {
                throw new RuntimeException('Could not copy the stored file.');
            }
            $copy->storage_path = $newPath;
        }

        $copy->save();

        if ($node->kind === DocumentNode::KIND_FOLDER) {
            foreach ($node->children as $child) {
                $this->copySubtree($child, $copy->id, $child->name);
            }
        }

        return $copy;
    }

    /** Accessible folders as a flat list — destination pickers for move/copy. */
    public function folderOptions(): array
    {
        return DocumentNode::query()
            ->accessible()
            ->where('kind', DocumentNode::KIND_FOLDER)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->all();
    }

    /**
     * Aggregate stats for a folder: recursive files/folders counts and total
     * size of all contained files (subfolders included), plus a count of the
     * items stored directly inside the folder.
     */
    public function folderStats(int $folderId): array
    {
        $folder = $this->requireAccessible($folderId);
        abort_if($folder->kind !== DocumentNode::KIND_FOLDER, 422, 'Node is not a folder.');

        $stats = $this->accumulateSubtreeStats($folder);

        return [
            'folderId' => $folder->id,
            'filesCount' => $stats['files'],
            'foldersCount' => $stats['folders'],
            'totalSizeBytes' => $stats['size'],
            'directItemsCount' => DocumentNode::query()
                ->where('parent_id', $folder->id)
                ->count(),
        ];
    }

    /** Recursive descent summing files, subfolders and stored sizes. */
    private function accumulateSubtreeStats(DocumentNode $folder): array
    {
        $files = 0;
        $folders = 0;
        $size = 0;

        $children = DocumentNode::query()
            ->where('parent_id', $folder->id)
            ->get(['id', 'kind', 'size_bytes']);

        foreach ($children as $child) {
            if ($child->kind === DocumentNode::KIND_FILE) {
                $files++;
                $size += (int) $child->size_bytes;

                continue;
            }

            $folders++;
            $sub = $this->accumulateSubtreeStats($child);
            $files += $sub['files'];
            $folders += $sub['folders'];
            $size += $sub['size'];
        }

        return ['files' => $files, 'folders' => $folders, 'size' => $size];
    }

    public function deleteNode(int $id): bool
    {
        $node = $this->requireWithPermission($id, DocumentNodeShare::PERM_DELETE);
        if ($node->kind === DocumentNode::KIND_FILE && $node->storage_path) {
            Storage::disk('local')->delete($node->storage_path);
        }
        // Children are removed by the FK cascade (nullOnDelete would orphan —
        // folders are removed together with their subtree instead).
        if ($node->kind === DocumentNode::KIND_FOLDER) {
            $this->deleteSubtree($node);
        }

        return (bool) $node->delete();
    }

    /**
     * Remove a broken shortcut — one whose target no longer exists. The
     * creator (or an admin) may clean it up; the UI shows it as a broken
     * link until then.
     */
    public function deleteBrokenShortcut(int $id): bool
    {
        $node = DocumentNode::query()->find($id);
        abort_unless($node && $node->kind === DocumentNode::KIND_SHORTCUT, 404, 'Shortcut not found.');
        abort_if($node->target_id !== null && DocumentNode::query()->whereKey($node->target_id)->exists(), 422, 'Shortcut is not broken.');

        // Only the owner or an admin may remove it (never shared editors).
        $user = Auth::user();
        $isAdmin = $user instanceof User && ($user->hasRole('admin') || $user->hasRole('super admin'));
        abort_if(! $isAdmin && (int) $node->owner_id !== (int) Auth::id(), 403, 'You can only manage shortcuts you own.');

        return (bool) $node->delete();
    }

    private function deleteSubtree(DocumentNode $folder): void
    {
        $children = DocumentNode::query()->where('parent_id', $folder->id)->get();
        foreach ($children as $child) {
            if ($child->kind === DocumentNode::KIND_FILE && $child->storage_path) {
                Storage::disk('local')->delete($child->storage_path);
            }
            if ($child->kind === DocumentNode::KIND_FOLDER) {
                $this->deleteSubtree($child);
            }
            $child->delete();
        }
    }

    /** The node the current user can actually open, else 404-equivalent null. */
    public function findAccessible(int $id): ?DocumentNode
    {
        return DocumentNode::query()
            ->with(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares', 'target:id,kind,name,target_id,extension,mime_type,size_bytes'])
            ->accessible()
            ->find($id);
    }

    public function requireAccessible(int $id): DocumentNode
    {
        $node = $this->findAccessible($id);
        abort_unless($node, 404, 'Document not found or not accessible.');

        return $node;
    }

    /** Node the current user may edit (owner or role 'admin'/'super admin'). */
    public function requireEditable(int $id): DocumentNode
    {
        return $this->requireWithPermission($id, DocumentNodeShare::PERM_WRITE);
    }

    /**
     * Node the current user may manage under a specific share permission.
     * Owner and admins pass always; otherwise the node must be protected and
     * explicitly shared with the user (directly or via one of their roles)
     * with the required action granted in the share's permissions CSV.
     */
    public function requireWithPermission(int $id, string $permission): DocumentNode
    {
        $node = DocumentNode::query()
            ->with(['shares', 'parent'])
            ->find($id);
        abort_unless($node, 404, 'Document not found.');

        $user = Auth::user();
        $isAdmin = $user instanceof User && ($user->hasRole('admin') || $user->hasRole('super admin'));
        if ($isAdmin || (int) $node->owner_id === (int) Auth::id()) {
            return $node;
        }

        // A shared user can only act within the permissions granted on their
        // share(s) of this node — direct user share or via one of their roles.
        $roleIds = $user instanceof User
            ? Role::query()->whereHas('users', fn (Builder $u) => $u->where('users.id', Auth::id()))->pluck('roles.id')
            : collect();

        $permitted = $node->shares->contains(
            fn (DocumentNodeShare $share) => $share->target_type === 'user'
                ? (int) $share->target_id === (int) Auth::id() && $share->grants($permission)
                : $roleIds->contains((int) $share->target_id) && $share->grants($permission)
        );

        abort_unless($permitted, 403, 'You do not have the "'.$permission.'" permission for this document.');

        return $node;
    }

    /** Streaming file download for a node the current user can access. */
    public function downloadStream(int $id): array
    {
        $node = $this->requireAccessible($id);
        abort_if($node->kind !== DocumentNode::KIND_FILE, 422, 'Folders cannot be downloaded.');
        abort_if(! $node->storage_path || ! Storage::disk('local')->exists($node->storage_path), 404, 'File is missing on storage.');

        return [
            'node' => $node,
            'stream' => Storage::disk('local')->readStream($node->storage_path),
            'name' => $node->name,
            'mime' => $node->mime_type ?? 'application/octet-stream',
        ];
    }

    /** Replace the explicit share list of a protected node. */
    public function syncShares(int $id, array $shareList, array $permissionsByTarget = []): DocumentNode
    {
        $node = $this->requireEditable($id);
        $userIds = array_values(array_filter($shareList, 'is_int')) ?: [];
        $roleIds = [];

        foreach ($shareList as $target) {
            if (is_string($target) && str_starts_with($target, 'role:')) {
                $roleIds[] = (int) substr($target, 5);
            }
        }

        // Server-side guard for the developer share rule: only admin, super
        // admin and developers may share with developer accounts or the
        // DEVELOPER role. Everyone else gets those targets stripped silently
        // (the picker never offered them — this catches hand-crafted calls).
        if (! self::canShareWithDevelopers()) {
            $developerUserIds = User::query()
                ->whereHas('roles', fn (Builder $r) => $r->where('code', 'DEVELOPER'))
                ->pluck('id')
                ->all();
            $developerRoleId = Role::query()->where('code', 'DEVELOPER')->value('id');
            $userIds = array_values(array_diff($userIds, $developerUserIds));
            $roleIds = array_values(array_diff($roleIds, $developerRoleId !== null ? [$developerRoleId] : []));
        }

        DocumentNodeShare::query()->where('document_node_id', $node->id)->delete();

        foreach ($userIds as $userId) {
            DocumentNodeShare::query()->create([
                'document_node_id' => $node->id,
                'target_type' => 'user',
                'target_id' => $userId,
                // Per-target action grants (read/write/copy/move/delete/share);
                // defaults to view-only when the caller sends nothing.
                'permissions' => DocumentNodeShare::normalizePermissions(
                    $permissionsByTarget['user:'.$userId] ?? [DocumentNodeShare::PERM_VIEW]
                ),
                'shared_by' => Auth::id(),
            ]);
        }
        foreach ($roleIds as $roleId) {
            DocumentNodeShare::query()->create([
                'document_node_id' => $node->id,
                'target_type' => 'role',
                'target_id' => $roleId,
                'permissions' => DocumentNodeShare::normalizePermissions(
                    $permissionsByTarget['role:'.$roleId] ?? [DocumentNodeShare::PERM_VIEW]
                ),
                'shared_by' => Auth::id(),
            ]);
        }

        // Sharing a node must make it visible to the shares — flip protected.
        // Removing the last share flips it back to private so the node does
        // not stay invisible to everyone except its owner/admins.
        $node->update(['visibility' => ($userIds || $roleIds)
            ? DocumentNode::VIS_PROTECTED
            : DocumentNode::VIS_PRIVATE,
        ]);

        return $node->fresh(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares']);
    }

    /**
     * Roles allowed to share WITH developer accounts / the DEVELOPER role:
     * admins, super admins and developers themselves. Everyone else never
     * sees developer targets in the share picker and cannot share to them.
     * Codes match the RoleSeeder.
     */
    public const DEVELOPER_SHARE_ALLOWED_ROLE_CODES = ['SUPER_ADMIN', 'ADMIN', 'DEVELOPER'];

    /** Can the given/current user share with developer users / the DEVELOPER role? */
    public static function canShareWithDevelopers(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->roles()
            ->whereIn('code', self::DEVELOPER_SHARE_ALLOWED_ROLE_CODES)
            ->exists();
    }

    /** Shareable targets for the share picker, filtered by privilege. */
    public function shareTargets(): array
    {
        // Developer accounts / the DEVELOPER role only appear for admin,
        // super admin and developer users; other users never see them.
        $developersVisible = self::canShareWithDevelopers();

        return [
            'users' => User::query()
                ->when(
                    ! $developersVisible,
                    // Non-privileged users: no developer accounts at all.
                    fn (Builder $q) => $q->whereDoesntHave('roles', fn (Builder $r) => $r->where('code', 'DEVELOPER'))
                )
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
            'roles' => Role::query()
                ->when(
                    ! $developersVisible,
                    fn ($q) => $q->where('code', '!=', 'DEVELOPER')
                )
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ];
    }

    public function taxonomy(): array
    {
        return [
            'categories' => DocumentCategory::query()->orderBy('name')->get(),
            'types' => DocumentType::query()->orderBy('name')->get(),
        ];
    }

    // ───────────────────────── helpers ─────────────────────────

    private function typeIdFor(string $extension): ?int
    {
        $map = [
            'pdf' => 'pdf',
            'doc' => 'word', 'docx' => 'word', 'odt' => 'word', 'rtf' => 'word',
            'xls' => 'spreadsheet', 'xlsx' => 'spreadsheet', 'csv' => 'spreadsheet', 'ods' => 'spreadsheet',
            'ppt' => 'presentation', 'pptx' => 'presentation', 'odp' => 'presentation',
            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image',
            'webp' => 'image', 'svg' => 'image', 'bmp' => 'image', 'heic' => 'image', 'tiff' => 'image',
            'txt' => 'text', 'md' => 'text', 'log' => 'text',
            'zip' => 'archive', 'rar' => 'archive', '7z' => 'archive', 'tar' => 'archive', 'gz' => 'archive',
        ];
        $slug = $map[$extension] ?? null;
        if (! $slug) {
            return null;
        }

        return DocumentType::query()->where('slug', $slug)->value('id');
    }

    /**
     * Who may add documents inside a folder: its owner, admins, and — since
     * shares inherit down the tree — anyone holding a 'write' grant on an
     * ancestor shared with them (a write grant on "kashi" also permits
     * creating inside "kashi/sub"). The nearest shared ancestor's grants win.
     */
    private function assertFolderWriteAccess(?int $parentId): void
    {
        if (! $parentId) {
            return; // root is a shared container
        }
        $parent = DocumentNode::query()->find($parentId);
        abort_unless($parent, 404, 'Parent folder not found.');
        abort_if($parent->kind !== DocumentNode::KIND_FOLDER, 422, 'Parent must be a folder.');
        $user = Auth::user();
        $isAdmin = $user instanceof User && ($user->hasRole('admin') || $user->hasRole('super admin'));
        if ($isAdmin || (int) $parent->owner_id === (int) Auth::id()) {
            return;
        }

        $this->assertSharedFolderWriteAccess($parent);
    }

    /**
     * Walk up from $folder looking for an ancestor shared with the current
     * user. The first share found (nearest ancestor wins) decides: a 'write'
     * grant passes; otherwise 403 with the view-only message.
     */
    private function assertSharedFolderWriteAccess(DocumentNode $folder): void
    {
        $user = Auth::user();
        $userId = Auth::id();
        $roleIds = $user instanceof User
            ? Role::query()->whereHas('users', fn (Builder $u) => $u->where('users.id', $userId))->pluck('roles.id')
            : collect();

        $cursor = $folder;
        while ($cursor) {
            $shares = $cursor->relationLoaded('shares')
                ? $cursor->shares
                : $cursor->shares()->get();

            $matched = $shares->first(
                fn (DocumentNodeShare $share) => $share->target_type === 'user'
                    ? (int) $share->target_id === (int) $userId
                    : $roleIds->contains((int) $share->target_id)
            );

            if ($matched) {
                abort_unless(
                    $matched->grants(DocumentNodeShare::PERM_WRITE),
                    403,
                    'You have read-only access to this folder.'
                );

                return; // write granted through this share
            }

            $cursor = $cursor->parent;
        }

        abort(403, 'You can only add documents inside folders you own.');
    }
}
