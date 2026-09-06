<?php

namespace Modules\DocumentManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * A single share of a document node with a user or a role.
 *
 * @property int $id
 * @property int $document_node_id
 * @property string $target_type
 * @property int $target_id
 * @property string $permissions
 * @property int|null $shared_by
 */
class DocumentNodeShare extends Model
{
    /** Granted actions for a share (CSV in the permissions column). */
    public const PERM_VIEW = 'view';

    public const PERM_WRITE = 'write';

    public const PERM_COPY = 'copy';

    public const PERM_MOVE = 'move';

    public const PERM_DELETE = 'delete';

    public const PERM_SHARE = 'share';

    public const PERMISSIONS = [
        self::PERM_VIEW,
        self::PERM_WRITE,
        self::PERM_COPY,
        self::PERM_MOVE,
        self::PERM_DELETE,
        self::PERM_SHARE,
    ];

    protected $table = 'document_node_shares';

    protected $fillable = ['document_node_id', 'target_type', 'target_id', 'permissions', 'shared_by'];

    public function node(): BelongsTo
    {
        return $this->belongsTo(DocumentNode::class, 'document_node_id');
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /** @return list<string> */
    public function grantedPermissions(): array
    {
        return array_values(array_filter(explode(',', (string) $this->permissions)));
    }

    public function grants(string $permission): bool
    {
        return in_array($permission, $this->grantedPermissions(), true);
    }

    /** @param list<string> $permissions */
    public function setPermissionsAttribute(array|string $permissions): void
    {
        $this->attributes['permissions'] = static::normalizePermissions($permissions);
    }

    /**
     * Normalize an incoming permission list: keep known actions only,
     * 'view' is always included (it is the floor of any share).
     *
     * @param  list<string>|string  $permissions
     * @return string CSV
     */
    public static function normalizePermissions(array|string $permissions): string
    {
        $granted = is_string($permissions)
            ? array_filter(explode(',', $permissions))
            : $permissions;
        $granted = array_intersect(static::PERMISSIONS, $granted);
        if (! in_array(self::PERM_VIEW, $granted, true)) {
            $granted[] = self::PERM_VIEW;
        }

        return implode(',', array_values(array_unique($granted)));
    }
}
