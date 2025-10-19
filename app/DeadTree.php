<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadTree extends Model
{
    const CREATED_AT = 'reported_at';
    const UPDATED_AT = null;
    
    protected $table = 'dead_trees';
    
    protected $fillable = [
        'tree_code',
        'reason',
        'image_path',
        'reported_at',
    ];

    protected $dates = ['reported_at'];

    /**
     * Relationship: DeadTree belongs to Tree
     */
    public function tree()
    {
        return $this->belongsTo(Tree::class, 'tree_code', 'code');
    }

    /**
     * Accessor for image URL (optional)
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }
}
