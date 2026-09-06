<?php

namespace Modules\DocumentManager\Controllers\Api;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\DocumentManager\Services\DocumentManagerService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentManagerController
{
    use ApiResponseTrait;

    public function __construct(private readonly DocumentManagerService $service) {}

    /** GET /document-manager/search?q= — name/description search over accessible nodes. */
    public function search(Request $request): JsonResponse
    {
        $term = (string) $request->query('q', '');
        $results = $this->service->search($term);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Search results retrieved',
            'data' => collect($results)
                ->map(fn (DocumentNode $node) => $this->nodeShape($node))
                ->values(),
        ]);
    }

    /** GET /document-manager/browse?folderId= — accessible folders + files. */
    public function browse(Request $request): JsonResponse
    {
        $folderId = $request->integer('folder_id') ?: null;
        $result = $this->service->browse($folderId);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Documents retrieved',
            'data' => [
                'folderId' => $result['folderId'],
                'breadcrumb' => collect($result['breadcrumb'])->map(
                    fn (DocumentNode $node) => $this->nodeShape($node)
                )->values(),
                'folders' => $result['folders']->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
                'files' => $result['files']->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
                'shortcuts' => $result['shortcuts']->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
            ],
        ]);
    }

    /** POST /document-manager/folders */
    public function storeFolder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'visibility' => ['nullable', 'in:private,protected,public'],
            'category_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $node = $this->service->createFolder($data);

        return response()->json([
            'success' => true,
            'code' => 201,
            'message' => 'Folder created',
            'data' => $this->nodeShape($node),
        ], 201);
    }

    /** POST /document-manager/upload (multipart: file, parentId, visibility, categoryId, typeId, description) */
    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'name' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'visibility' => ['nullable', 'in:private,protected,public'],
            'category_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $node = $this->service->upload($data);

        return response()->json([
            'success' => true,
            'code' => 201,
            'message' => 'File uploaded',
            'data' => $this->nodeShape($node),
        ], 201);
    }

    /**
     * POST /document-manager/shortcuts — { targetId, parentId?, name? }.
     * Creates a link node pointing at the target inside parentId (root if
     * omitted).
     */
    public function storeShortcut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'targetId' => ['required', 'integer'],
            'parentId' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);
        $node = $this->service->createShortcut(
            (int) $data['targetId'],
            $data['parentId'] ?? null,
            $data['name'] ?? null
        );

        return response()->json([
            'success' => true,
            'code' => 201,
            'message' => 'Shortcut created',
            'data' => $this->nodeShape($node->fresh(['target:id,kind,name,target_id,extension,mime_type,size_bytes'])),
        ], 201);
    }

    /**
     * GET /document-manager/shortcuts/{shortcut}/resolve — where the link
     * points. The frontend navigates to target.parentId and marks the target;
     * a broken link 410s so the UI can offer cleanup.
     */
    public function resolveShortcut(int $shortcut): JsonResponse
    {
        $node = DocumentNode::query()
            ->with(['target'])
            ->accessible()
            ->findOrFail($shortcut);
        abort_unless($node->kind === DocumentNode::KIND_SHORTCUT, 404, 'Shortcut not found.');

        $target = $node->target;
        if (! $target) {
            return response()->json([
                'success' => false,
                'code' => 410,
                'message' => 'The shortcut target no longer exists.',
                'data' => ['shortcutId' => $node->id],
            ], 410);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Shortcut resolved',
            'data' => $this->nodeShape($target->fresh(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares'])),
        ]);
    }

    /** DELETE /document-manager/shortcuts/{shortcut} — removes a broken link. */
    public function destroyShortcut(int $shortcut): JsonResponse
    {
        $ok = $this->service->deleteBrokenShortcut($shortcut);
        if (! $ok) {
            return response()->json(['success' => false, 'code' => 404, 'message' => 'Shortcut not found'], 404);
        }

        return response()->json(['success' => true, 'code' => 200, 'message' => 'Shortcut deleted']);
    }

    /** POST /document-manager/nodes/{node}/copy — { parentId } (null/omitted = root). */
    public function copy(Request $request, int $node): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'conflict' => ['nullable', 'in:replace,rename'],
        ]);
        $copy = $this->service->copyNode($node, $data['parent_id'] ?? null, $data['conflict'] ?? 'rename');

        return response()->json([
            'success' => true,
            'code' => 201,
            'message' => 'Document copied',
            'data' => $this->nodeShape($copy->fresh(['owner:id,name', 'category:id,name,color', 'type:id,name', 'shares'])),
        ], 201);
    }

    /** GET /document-manager/shared-with-me — nodes others shared with the current user. */
    public function sharedWithMe(): JsonResponse
    {
        $result = $this->service->sharedWithMe();

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Shared documents retrieved',
            'data' => [
                'folders' => collect($result['folders'])->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
                'files' => collect($result['files'])->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
                'shortcuts' => collect($result['shortcuts'])->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
            ],
        ]);
    }

    /** GET /document-manager/shared-by-me — the current user's nodes shared outward. */
    public function sharedByMe(): JsonResponse
    {
        $result = $this->service->sharedByMe();

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Shared documents retrieved',
            'data' => [
                'folders' => collect($result['folders'])->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
                'files' => collect($result['files'])->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
                'shortcuts' => collect($result['shortcuts'])->map(fn (DocumentNode $node) => $this->nodeShape($node))->values(),
            ],
        ]);
    }

    /** GET /document-manager/folders — flat accessible folder list for move/copy pickers. */
    public function folders(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Folders retrieved',
            'data' => collect($this->service->folderOptions())->map(
                fn (DocumentNode $folder) => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parentId' => $folder->parent_id,
                ]
            )->values(),
        ]);
    }

    /** PATCH /document-manager/nodes/{node} */
    public function updateNode(Request $request, int $node): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', 'in:private,protected,public'],
            'category_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer'],
            'conflict' => ['nullable', 'in:replace,rename'],
        ]);
        $node = $this->service->updateNode($node, $data);

        return $this->resourceResponse(
            new JsonResource($this->nodeShape($node)),
            'Document updated'
        );
    }

    /**
     * POST /document-manager/conflicts — { ids: [], parentId } reports which
     * of the nodes would collide with destination siblings, so the frontend
     * can offer Skip / Replace / Keep both before mutating anything.
     */
    public function conflicts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'parentId' => ['nullable', 'integer'],
            'excludeId' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Conflicts checked',
            'data' => $this->service->conflictsFor(
                $data['ids'],
                $data['parentId'] ?? null,
                $data['excludeId'] ?? null
            ),
        ]);
    }

    /** DELETE /document-manager/nodes/{node} */
    public function destroy(int $node): JsonResponse
    {
        $ok = $this->service->deleteNode($node);
        if (! $ok) {
            return response()->json(['success' => false, 'code' => 404, 'message' => 'Document not found'], 404);
        }

        return response()->json(['success' => true, 'code' => 200, 'message' => 'Document deleted']);
    }

    /** GET /document-manager/nodes/{node}/stats — recursive contents summary. */
    public function nodeStats(int $node): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Folder stats retrieved',
            'data' => $this->service->folderStats($node),
        ]);
    }

    /** GET /document-manager/nodes/{node}/download */
    public function download(int $node): StreamedResponse
    {
        $download = $this->service->downloadStream($node);

        return response()->streamDownload(function () use ($download) {
            fpassthru($download['stream']);
        }, $download['name'], ['Content-Type' => $download['mime']]);
    }

    /** GET /document-manager/nodes/{node}/preview — inline stream for in-app viewing. */
    public function preview(int $node): StreamedResponse
    {
        $download = $this->service->downloadStream($node);

        return response()->stream(function () use ($download) {
            fpassthru($download['stream']);
        }, 200, [
            'Content-Type' => $download['mime'],
            'Content-Disposition' => 'inline; filename="'.addslashes($download['name']).'"',
        ]);
    }

    /** PUT /document-manager/nodes/{node}/shares — { userIds: [], roleIds: [], permissions: {…} } */
    public function syncShares(Request $request, int $node): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer'],
            // Per-target action grants, keyed "user:3" / "role:5":
            // ["view","write","copy","move","delete","share"]
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string'],
        ]);

        $shareList = array_merge(
            array_map('intval', $data['user_ids'] ?? []),
            array_map(fn ($id) => 'role:'.(int) $id, $data['role_ids'] ?? [])
        );
        $permissionsByTarget = [];
        foreach (($data['permissions'] ?? []) as $key => $actions) {
            $permissionsByTarget[(string) $key] = array_values(array_map('strval', $actions));
        }
        $node = $this->service->syncShares($node, $shareList, $permissionsByTarget);

        return $this->resourceResponse(new JsonResource($this->nodeShape($node)), 'Sharing updated');
    }

    /** GET /document-manager/meta — categories, types, share targets. */
    public function meta(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Meta retrieved',
            'data' => $this->service->taxonomy(),
        ]);
    }

    /** GET /document-manager/share-targets */
    public function shareTargets(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Share targets retrieved',
            'data' => $this->service->shareTargets(),
        ]);
    }

    private function nodeShape(DocumentNode $node): array
    {
        $owner = $node->relationLoaded('owner') ? $node->owner : null;

        return [
            'id' => $node->id,
            'name' => $node->name,
            'kind' => $node->kind,
            'visibility' => $node->visibility,
            'parentId' => $node->parent_id,
            'targetId' => $node->target_id,
            'target' => $node->relationLoaded('target') && $node->target
                ? [
                    'id' => $node->target->id,
                    'name' => $node->target->name,
                    'kind' => $node->target->kind,
                    'parentId' => $node->target->parent_id,
                    'extension' => $node->target->extension,
                    'mimeType' => $node->target->mime_type,
                    'sizeBytes' => $node->target->size_bytes,
                ]
                : null,
            'ownerId' => $node->owner_id,
            'ownerName' => $owner?->name,
            'category' => $node->relationLoaded('category') && $node->category
                ? ['id' => $node->category->id, 'name' => $node->category->name, 'color' => $node->category->color]
                : null,
            'type' => $node->relationLoaded('type') && $node->type
                ? ['id' => $node->type->id, 'name' => $node->type->name]
                : null,
            'description' => $node->description,
            'mimeType' => $node->mime_type,
            'extension' => $node->extension,
            'sizeBytes' => $node->size_bytes,
            'downloadUrl' => $node->kind === DocumentNode::KIND_FILE
                ? "/document-manager/nodes/{$node->id}/download"
                : null,
            'previewUrl' => $node->kind === DocumentNode::KIND_FILE
                ? "/document-manager/nodes/{$node->id}/preview"
                : null,
            'sharedWith' => $node->relationLoaded('shares')
                ? $node->shares->map(fn ($share) => [
                    'targetType' => $share->target_type,
                    'targetId' => $share->target_id,
                    // Granted actions for this share (view/write/copy/move/delete/share).
                    'permissions' => $share->grantedPermissions(),
                ])->values()
                : [],
            'createdAt' => optional($node->created_at)->toISOString(),
            'updatedAt' => optional($node->updated_at)->toISOString(),
        ];
    }
}
