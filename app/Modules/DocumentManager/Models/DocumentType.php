<?php

namespace Modules\DocumentManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $mime_category
 */
class DocumentType extends Model
{
    protected $table = 'document_types';

    protected $fillable = ['name', 'slug', 'mime_category'];

    public function nodes(): HasMany
    {
        return $this->hasMany(DocumentNode::class, 'type_id');
    }
}
