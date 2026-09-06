<?php

namespace Modules\DocumentManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 */
class DocumentCategory extends Model
{
    protected $table = 'document_categories';

    protected $fillable = ['name', 'slug', 'color'];

    public function nodes(): HasMany
    {
        return $this->hasMany(DocumentNode::class, 'category_id');
    }
}
