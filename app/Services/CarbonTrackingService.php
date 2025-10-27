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

    //     public function updateCarbonForTreeData(TreeData $treeData, array $params = [])
    // {
    //     // Example: compute biomass/carbon/sequestration
    //     // You can replace this with your actual formula
    //     $dbh   = $treeData->dbh ?? 0;
    //     $height = $treeData->height ?? 0;

    //     $estimatedBiomass = ($dbh * $height) * ($params['alpha'] ?? 0.5);
    //     $carbonStock      = $estimatedBiomass * 0.5;
    //     $annualSeq        = $carbonStock * 0.1;

    //     $treeData->update([
    //         'estimated_biomass_kg'       => $estimatedBiomass,
    //         'carbon_stock_kg'            => $carbonStock,
    //         'annual_sequestration_kgco2' => $annualSeq,
    //     ]);

    //     return $treeData->fresh();
    // }


    /**
     * Update sequestration values directly on TreeData
     */
    // public function updateCarbonForTreeData(TreeData $treeData, array $params = [])
    // {
    //     $dbh    = $treeData->dbh ?? 0;           // in cm
    //     $height = $treeData->height ?? 0;        // in meters
    //     $rho    = $params['wood_density'] ?? 0.6; // g/cm³ (default average)

    //     // Calculate Above-Ground Biomass (AGB) using Chave et al. (2014)
    //     if ($dbh > 0 && $height > 0 && $rho > 0) {
    //         $agb = 0.0673 * pow(($rho * pow($dbh, 2) * $height), 0.976); // in kg

    //         // Convert AGB to Carbon Stock
    //         $carbonStock = $agb * 0.47; // 47% of AGB is carbon (IPCC default)

    //         // Estimate annual sequestration (e.g., 5% of carbon stock per year)
    //         $growthRate = $params['growth_rate'] ?? 0.05; // default: 5%
    //         $annualSequestration = $carbonStock * $growthRate; // in kg of C/year

    //         // Optionally convert annual sequestration to CO2 equivalent
    //         // 1 kg C = 3.67 kg CO2
    //         $annualSequestrationCO2 = $annualSequestration * 3.67;

    //     } else {
    //         // Handle edge cases: invalid input
    //         $agb = 0;
    //         $carbonStock = 0;
    //         $annualSequestrationCO2 = 0;
    //     }

    //     // Update TreeData
    //     $treeData->update([
    //         'estimated_biomass_kg'       => $agb,
    //         'carbon_stock_kg'            => $carbonStock,
    //         'annual_sequestration_kgco2' => $annualSequestrationCO2,
    //     ]);

    //     return $treeData->fresh();
    // }

    public function updateCarbonForTreeData(TreeData $treeData, array $params = [])
    {
        $dbh    = $treeData->dbh ?? 0;            // cm
        $height = $treeData->height ?? 0;         // m
        $rho    = $params['wood_density'] ?? 0.6; // g/cm³

        $agb = 0;
        $carbonStock = 0;
        $annualSequestrationCO2 = 0;

        if ($dbh > 0 && $height > 0 && $rho > 0) {
            // Current AGB using Chave et al. (2014)
            $agb = 0.0673 * pow(($rho * pow($dbh, 2) * $height), 0.976);
            $carbonStock = $agb * 0.47;

            if (isset($params['dbh_prev'], $params['height_prev'])) {
                // Increment-based (preferred)
                $dbhPrev    = $params['dbh_prev'];
                $heightPrev = $params['height_prev'];
                $agbPrev = 0.0673 * pow(($rho * pow($dbhPrev, 2) * $heightPrev), 0.976);
                $deltaAgb = max(0, $agb - $agbPrev);
                $annualSequestrationCO2 = $deltaAgb * 0.47 * 3.67;
            } else {
                // Stock-based fallback: use a conservative growth rate (e.g., 1–2%)
                $growthRate = $params['growth_rate'] ?? 0.015; // 1.5% default
                $annualSequestrationCO2 = $carbonStock * $growthRate * 3.67;
            }
        }

        $treeData->update([
            'estimated_biomass_kg'       => $agb,
            'carbon_stock_kg'            => $carbonStock,
            'annual_sequestration_kgco2' => $annualSequestrationCO2,
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