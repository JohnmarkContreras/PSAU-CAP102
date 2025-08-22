<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Harvest extends Model
{
    protected $fillable = ['code', 'harvest_date', 'harvest_weight_kg', 'quality', 'notes'];

    public function tree()
    {
        return $this->belongsTo(Tree::class, 'code', 'code');
    }
}
