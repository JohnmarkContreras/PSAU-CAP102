<?php

namespace App\Services;

use App\TreeData;
use Carbon\Carbon;
use App\User;
class CarbonTrackingService
{
    public function generateChartData($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate   = $endDate ?? now();

        return $this->getChartDataForPeriod($startDate, $endDate);
    }

    /**
     * Update sequestration values directly on TreeData
     */
    public function updateCarbonForTreeData(TreeData $treeData, array $params = [])
    {
        // Example: compute biomass/carbon/sequestration
        // You can replace this with your actual formula
        $dbh   = $treeData->dbh ?? 0;
        $height = $treeData->height ?? 0;

        $estimatedBiomass = ($dbh * $height) * ($params['alpha'] ?? 0.5);
        $carbonStock      = $estimatedBiomass * 0.5;
        $annualSeq        = $carbonStock * 0.1;

        $treeData->update([
            'estimated_biomass_kg'       => $estimatedBiomass,
            'carbon_stock_kg'            => $carbonStock,
            'annual_sequestration_kgco2' => $annualSeq,
        ]);

        return $treeData->fresh();
    }

    /**
     * Build chart data from tree_data table
     */
    private function getChartDataForPeriod($startDate, $endDate)
    {
        return TreeData::whereBetween('created_at', [$startDate, $endDate])
            ->with('treeCode') // assuming TreeData belongsTo TreeCode
            ->get()
            ->groupBy('tree_code_id')
            ->map(function ($records, $treeCodeId) {
                return [
                    'tree_code'            => optional($records->first()->treeCode)->code,
                    'sequestration_series' => $records->pluck('annual_sequestration_kgco2'),
                    'dates'                => $records->pluck('created_at'),
                ];
            });
    }
}