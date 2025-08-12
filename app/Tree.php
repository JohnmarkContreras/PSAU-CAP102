<?php

namespace App;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Tree extends Model
{
    protected $fillable = [
        'code', 
        'type',
        'age',
        'height',
        'stem_diameter',
        'canopy_diameter',
        'latitude',
        'longitude',
    ];

    public function carbonRecords()
    {
        return $this->hasMany(CarbonRecord::class);
    }

    protected static function booted()
{
    static::created(function ($tree) {
        // Dummy logic – replace with real formulas later
        $biomass = 0.25 * pow($tree->diameter_cm, 2) * $tree->height_m;
        $carbon = 0.5 * $biomass; // Assume 50% is carbon
        $sequestration = $carbon * 0.05; // Assume 5% annual uptake

        $tree->carbonRecords()->create([
            'estimated_biomass_kg' => $biomass,
            'carbon_stock_kg' => $carbon,
            'annual_sequestration_kg' => $sequestration,
            'recorded_at' => now(),
        ]);
    });
}
    #activity log
    use LogsActivity;

        protected static $logAttributes = [
            'type',
            'age_years',
            'height_m',
            'stem_diameter_cm',
            'canopy_diameter_m'
        ];

        protected static $logOnlyDirty = true;
        protected static $logName = 'tamarind_tree';
}


