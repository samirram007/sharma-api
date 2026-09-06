<?php

namespace Modules\DocumentManager\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

/**
 * A node in the document tree: a folder, an uploaded file, or a shortcut
 * pointing at another node via target_id.
 *
 * @property int $id
 * @property string $name
 * @property string $kind
 * @property string $visibility
 * @property int|null $parent_id
 * @property int|null $target_id
 * @property int $owner_id
 * @property int|null $category_id
 * @property int|null $type_id
 * @property string|null $description
 * @property string|null $mime_type
 * @property string|null $extension
 * @property string|null $storage_path
 * @property int|null $size_bytes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read self|null $parent
 * @property-read self|null $target
 * @property-read Collection<int, self> $children
 * @property-read User|null $owner
 * @property-read DocumentCategory|null $category
 * @property-read DocumentType|null $type
 * @property-read Collection<int, DocumentNodeShare> $shares
 */
class DocumentNode extends Model
{
    use Blameable;

    protected $table = 'document_nodes';

    public const KIND_FOLDER = 'folder';

    public const KIND_FILE = 'file';

    public const KIND_SHORTCUT = 'shortcut';

    public const VIS_PRIVATE = 'private';

    public const VIS_PROTECTED = 'protected';

    public const VIS_PUBLIC = 'public';

    protected $fillable = [
        'name',
        'kind',
        'visibility',
        'parent_id',
        'target_id',
        'owner_id',
        'category_id',
        'type_id',
        'description',
        'mime_type',
        'extension',
        'storage_path',
        'size_bytes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Shortcut destination — null (or missing row) means a broken link. */
    public function target(): BelongsTo
    {
        return $this->belongsTo(self::class, 'target_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('kind')->orderBy('name');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'type_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentNodeShare::class, 'document_node_id');
    }

    /**
     * Folder ids the current user reaches through ownership or share
     * inheritance: every folder they own, plus every folder that is
     * company-public or explicitly shared with them (directly or via a
     * role) — each carrying its whole subtree along, the same convention
     * as Drive-style folder sharing. Returns the folder AND all its
     * descendant folder ids, so any node whose parent_id is in the list is
     * reachable. Seeding with owned folders is what lets an owner see
     * items co-sharers create inside their folders (those nodes are
     * owned by the creator, so `owner_id` alone can't match them).
     */
    public static function inheritedAccessFolderIds(?int $userId = null): array
    {
        $userId ??= Auth::id();
        if ($userId === null) {
            return [];
        }

        $roleIds = Role::query()->whereHas('users', fn (Builder $u) => $u->where('users.id', $userId))
            ->pluck('roles.id');

        $roots = static::query()
            ->where('kind', self::KIND_FOLDER)
            ->where(function (Builder $q) use ($userId, $roleIds) {
                $q->where('owner_id', $userId)
                    ->orWhere('visibility', self::VIS_PUBLIC)
                    ->orWhere(fn (Builder $sub) => $sub->sharedWithUser($userId, $roleIds));
            })
            ->pluck('id')
            ->all();

        // Walk down from the entry folders collecting descendant folders so
        // files nested at any depth resolve through parent_id membership.
        $all = $roots;
        $frontier = $roots;
        while ($frontier !== []) {
            $children = static::query()
                ->where('kind', self::KIND_FOLDER)
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();
            $frontier = array_values(array_diff($children, $all));
            $all = array_merge($all, $frontier);
        }

        return $all;
    }

    /**
     * Protected nodes explicitly shared with the given user — directly
     * (target_type 'user') or through one of their roles.
     */
    public function scopeSharedWithUser(Builder $query, int $userId, SupportCollection $roleIds): Builder
    {
        return $query->where('visibility', self::VIS_PROTECTED)
            ->whereIn('id', function ($inner) use ($userId, $roleIds) {
                $inner->select('document_node_id')->from('document_node_shares')
                    ->where(function ($share) use ($userId, $roleIds) {
                        $share->where(function ($userShare) use ($userId) {
                            $userShare->where('target_type', 'user')->where('target_id', $userId);
                        });
                        if ($roleIds->isNotEmpty()) {
                            $share->orWhere(function ($roleShare) use ($roleIds) {
                                $roleShare->where('target_type', 'role')->whereIn('target_id', $roleIds);
                            });
                        }
                    });
            });
    }

    /**
     * Nodes the signed-in user may browse: their own (any visibility),
     * everything nested inside folders they own (co-sharers' creations
     * included), company-public nodes, protected nodes explicitly shared
     * with them (directly or through one of their roles), and everything
     * nested inside a shared/public folder — access inherits down the tree.
     */
    public function scopeAccessible(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= Auth::id();

        // No authenticated user (tinker, queue, pre-auth request) — the scope
        // must match nothing rather than crash passing null into the share
        // sub-scopes.
        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($userId) {
            $q->where('owner_id', $userId)
                ->orWhere('visibility', self::VIS_PUBLIC)
                ->orWhere(function (Builder $sub) use ($userId) {
                    $roleIds = Role::query()->whereHas('users', fn (Builder $u) => $u->where('users.id', $userId))
                        ->pluck('roles.id');

                    $sub->sharedWithUser($userId, $roleIds);
                })
                // Descendants of shared/public folders inherit the access.
                ->orWhereIn('parent_id', self::inheritedAccessFolderIds($userId));
        });
    }
}
