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

    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'code', 'code');
    }

    // Latest measurement for convenience
    public function latestTreeData()
    {
        return $this->hasOne(TreeData::class)->latest('id');
    }

    // Latest harvest prediction by code
    public function latestPrediction()
    {
return $this->hasOne(HarvestPrediction::class, 'code', 'code')
            ->where('status', 'pending')
            ->orderByDesc('created_at'); // or 'id' if that's your timestamp proxy
    }
    // Latest tree data entry
    public function latestData()
    {
        return $this->hasOne(\App\TreeData::class, 'tree_code_id', 'id')
            ->latest('created_at');
    }

    //for treecode
    public function treeType()
    {
        return $this->belongsTo(\App\TreeType::class, 'tree_type_id', 'id');
    }
    
    // Relationship: has many tree data entries
    public function treeData()
    {
        return $this->hasMany(\App\TreeData::class, 'tree_code_id', 'id');
    }

    // optionally inverse to HarvestPrediction(s) that reference code
    public function harvestPredictions()
    {
        return $this->hasMany(HarvestPrediction::class, 'code', 'code');
    }
}