<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileGeotagMetadata extends Model
{
    protected $fillable = [
        'pending_geotag_id',
        'tree_data_id',
        'image',
        'device_id',
        'source',
    ];

    /**
     * Link to the pending geotag this metadata belongs to.
     */
    public function pendingGeotag(): BelongsTo
    {
        return $this->belongsTo(PendingGeotag::class);
    }

    /**
     * Optional link to the tree this metadata may be associated with.
     */
    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }
}
