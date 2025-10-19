<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadTreeRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tree_code', 'reason', 'image_path', 'status', 'submitted_by', 'submitted_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class, 'tree_code', 'code');
    }
}
