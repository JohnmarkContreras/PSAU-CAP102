<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TreeCode extends Model
{

    protected $table = 'tree_code';

    protected $fillable = [
        'tree_image_id',
        'tree_type_id',
        'code',
        'created_by',
    ];

    /**
     * Relationship: belongs to a tree image
     */
    public function treeImage()
    {
        return $this->belongsTo(TreeImage::class);
    }

    // Relationship: belongs to a tree type
    public function treeType()
    {
        return $this->belongsTo(TreeType::class);
    }

    /**
     * Relationship: created by a user (optional)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship: has many measurements
    public function measurements()
    {
        return $this->hasMany(TreeMeasurement::class);
    }

    // Relationship: has many tree data entries
    public function treeData()
    {
        return $this->hasMany(TreeData::class);
    }
}
