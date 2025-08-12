<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CarbonRecord extends Model
{
        protected $fillable = [
        'tree_id',
        'estimated_biomass_kg',
        'carbon_stock_kg',
        'annual_sequestration_kg',
        'recorded_at',
    ];

    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
}
