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

    // Latest measurement for convenience
    public function latestTreeData()
    {
        return $this->hasOne(TreeData::class)->latest('id');
    }

    // Latest harvest prediction by code
    public function latestPrediction()
    {
        return $this->hasOne(HarvestPrediction::class, 'code', 'code')->orderBy('predicted_date', 'desc');
    }
    // Latest tree data entry
    public function latestData()
    {
        return $this->hasOne(\App\TreeData::class, 'tree_code_id', 'id')
            ->latest('created_at');
    }

}
