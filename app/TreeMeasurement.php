<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TreeMeasurement extends Model
{
    protected $fillable = [
        'tree_code_id',
        'age',
        'height',
        'canopy_diameter',
        'stem_diameter',
    ];

    // Relationship: belongs to a tree code
    public function treeCode()
    {
        return $this->belongsTo(TreeCode::class);
    }
}