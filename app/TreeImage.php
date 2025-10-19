<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TreeImage extends Model
    {
        protected $fillable = [
        'filename', 'latitude', 'longitude', 'accuracy', 'taken_at', 'source_type'
    ];

    public function code()
    {
        return $this->hasOne(TreeCode::class, 'tree_image_id');
    }

    public function treeData()
    {
        return $this->hasOne(TreeData::class, 'id', 'tree_image_id');
    }
}
