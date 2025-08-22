<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HarvestPrediction extends Model
{
    protected $fillable = ['code', 'predicted_date', 'predicted_quantity'];

    public function tree()
    {
        return $this->belongsTo(Tree::class, 'code', 'code');
    }
}
