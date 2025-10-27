<?php

namespace App;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
class TreeData extends Model
{

    public function getAgeAttribute()
    {
        $now = Carbon::now();

        if ($this->planted_at) {
            $planted = $this->planted_at instanceof Carbon
                ? $this->planted_at
                : Carbon::parse($this->planted_at);

            return $this->planted_year_only
                ? $now->year - $planted->year   // year-only flag set
                : $planted->diffInYears($now);  // full date, precise diff
        }

        if ($this->planted_year_only) {
            // If no planted_at but we have a year-only value stored
            return $now->year - (int) $this->planted_year_only;
        }

        return null;
    }

    protected $table = 'tree_data';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'tree_code_id',
        'dbh',
        'height',
        'age',
        'stem_diameter',
        'canopy_diameter',
        'planted_at',
        'planted_year_only',
        'estimated_biomass_kg',
        'carbon_stock_kg',
        'annual_sequestration_kgco2',
        'harvests',
    ];

    use LogsActivity;

    protected static $logAttributes = [
        'tree_code_id',
        'dbh',
        'height',
        'age',
        'latitude',
        'longitude'
    ];

    protected static $logName = 'tree_data';
    protected static $logOnlyDirty = true; // Only log changed fields

    public function getDescriptionForEvent(string $eventName): string
    {
        return "TreeData record was {$eventName} by " . auth()->user()->name;
    }

    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
    /**
     * Relationship: Each tree data belongs to a tree code
     */
    // public function treeCode()
    // {
    //     return $this->belongsTo(TreeCode::class);
    // }

    /**
     * Carbon sequestration calculation (in pounds of CO₂)
     */
    public function computeAndSaveCarbon(array $params = [], bool $save = true): array
    {
        $alpha = isset($params['alpha']) ? (float)$params['alpha'] : 0.05;
        $carbonFraction = isset($params['carbon_fraction']) ? (float)$params['carbon_fraction'] : 0.50;
        $growth = isset($params['annual_growth_fraction']) ? (float)$params['annual_growth_fraction'] : 0.02;
        $cToCo2 = 44.0 / 12.0;

        $dbh_cm = (float) ($this->dbh ?? 0);   // centimeters
        $height = (float) ($this->height ?? 0); // meters

        $biomass = $alpha * ($dbh_cm * $dbh_cm) * $height; // kg
        $carbonStock = $biomass * $carbonFraction;        // kg C
        $annualCgain = $carbonStock * $growth;            // kg C / yr
        $annualCo2 = $annualCgain * $cToCo2;              // kg CO2 / yr

        $payload = [
            'dbh_cm' => round($dbh_cm, 4),
            'estimated_biomass_kg' => round($biomass, 4),
            'carbon_stock_kg' => round($carbonStock, 4),
            'annual_sequestration_kgco2' => round($annualCo2, 4),
            'alpha' => $alpha,
            'carbon_fraction' => $carbonFraction,
            'annual_growth_fraction' => $growth,
        ];

        if ($save) {
            $this->estimated_biomass_kg = $payload['estimated_biomass_kg'];
            $this->carbon_stock_kg = $payload['carbon_stock_kg'];
            $this->annual_sequestration_kgco2 = $payload['annual_sequestration_kgco2'];

            if (Schema::hasColumn($this->getTable(), 'dbh_cm')) {
                $this->dbh_cm = $payload['dbh_cm'];
            }

            // persist parameters optionally
            if (Schema::hasColumn($this->getTable(), 'alpha')) {
                $this->alpha = $payload['alpha'];
            }
            if (Schema::hasColumn($this->getTable(), 'carbon_fraction')) {
                $this->carbon_fraction = $payload['carbon_fraction'];
            }
            if (Schema::hasColumn($this->getTable(), 'annual_growth_fraction')) {
                $this->annual_growth_fraction = $payload['annual_growth_fraction'];
            }

            $this->save();
        }

        return $payload;
    }

    // Project carbon sequestration year-by-year up to a target year (default 10 years out)
    public function projectCarbonSequestrationByDate($targetYear = null)
    {
        $alpha = $this->alpha ?? 0.05;
        $carbonFraction = $this->carbon_fraction ?? 0.5;
        $growth = $this->annual_growth_fraction ?? 0.02;
        $cToCo2 = 44 / 12;

        $dbh = $this->dbh; // cm
        $height = $this->height; // m

        // Base year = planting or created_at
        $start = $this->planting_date ?? $this->created_at;
        $startYear = date('Y', strtotime($start));

        // Default: 10 years from start
        $targetYear = $targetYear ?? ($startYear + 10);
        $years = $targetYear - $startYear;

        $biomass = $alpha * pow($dbh, 2) * $height;
        $carbonStock = $biomass * $carbonFraction;

        // Annual CO₂ sequestration
        $annualCO2 = $carbonStock * $growth * $cToCo2;

        // Project year-by-year
        $projection = [];
        for ($i = 1; $i <= $years; $i++) {
            $projection[$startYear + $i] = round($annualCO2 * pow(1 + $growth, $i - 1), 2);
        }

        return [
            'start_year' => $startYear,
            'target_year' => $targetYear,
            'projection' => $projection,
        ];
    }

    public function treeCode()
    {
        // tree_data.tree_code_id → tree_code.id
        return $this->belongsTo(TreeCode::class, 'tree_code_id', 'id');
    }
    protected $casts = [
        'planted_year_only' => 'boolean',
        'planted_at' => 'date',
    ];
}