<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Harvest extends Model
{
    protected $fillable = ['code', 'harvest_date', 'harvest_weight_kg', 'quality', 'notes'];

    // Keep method name 'tree' for backward compatibility, but point to TreeCode
    public function tree()
    {
        return $this->belongsTo(TreeCode::class, 'code', 'code');
    }

    public function treeCode()
    {
        return $this->belongsTo(TreeCode::class, 'code', 'code');
    }
    
}
