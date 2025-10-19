<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CarbonRecord extends Model
{
        protected $fillable = [
        'tree_id',
        // 'tree_data_id',
        'estimated_biomass_kg',
        'carbon_stock_kg',
        'annual_sequestration_kg',
        'recorded_at',
    ];

    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }

    public function treeData()
    {
        return $this->belongsTo(TreeData::class, 'tree_code_id');
    }

        public function treeCode()
    {
        return $this->belongsTo(TreeCode::class);
    }
}
