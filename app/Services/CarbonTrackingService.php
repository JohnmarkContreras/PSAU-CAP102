<?php

namespace App\Services;

use App\Tree;
use App\CarbonRecord;
use Carbon\Carbon;

class CarbonTrackingService
{
    public function generateChartData($startDate = null, $endDate = null)
    {
        $this->ensureDailyCarbonRecords();
        
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return $this->getChartDataForPeriod($startDate, $endDate);
    }

    private function ensureDailyCarbonRecords()
    {
        $trees = Tree::all();
        $today = now()->toDateString();

        foreach ($trees as $tree) {
            if (!$this->hasRecordForDate($tree->id, $today)) {
                $this->createCarbonRecord($tree);
            }
        }
    }

    private function hasRecordForDate($treeId, $date)
    {
        return CarbonRecord::whereDate('recorded_at', $date)
            ->where('tree_id', $treeId)
            ->exists();
    }

    private function createCarbonRecord(Tree $tree)
    {
        CarbonRecord::create([
            'tree_id' => $tree->id,
            'estimated_biomass_kg' => $tree->estimated_biomass_kg,
            'carbon_stock_kg' => $tree->carbon_stock_kg,
            'annual_sequestration_kg' => $tree->annual_sequestration_kg,
            'recorded_at' => now(),
        ]);
    }

    private function getChartDataForPeriod($startDate, $endDate)
    {
        return CarbonRecord::whereBetween('recorded_at', [$startDate, $endDate])
            ->with('tree')
            ->get()
            ->groupBy('tree_id')
            ->map(function ($records, $treeId) {
                return [
                    'tree_code' => $records->first()->tree->code,
                    'sequestration_series' => $records->pluck('annual_sequestration_kg'),
                    'dates' => $records->pluck('recorded_at'),
                ];
            });
    }
}