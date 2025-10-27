<?php

namespace App\Services;

use App\Tree;
use App\User;
class TreeAnalyticsService
{
    public function getAnalyticsData()
    {
        return [
            'sweet' => $this->getTreeCountByType('sweet'),
            'semi' => $this->getTreeCountByType('semi_sweet'),
            'sour' => $this->getTreeCountByType('sour'),
            'total' => $this->getTotalTreeCount(),
            'trees' => $this->getTreesWithCarbonStats(),
            'chartData' => $this->getChartData(),
        ];
    }

    private function getTreeCountByType($type)
    {
        return Tree::where('type', $type)->count();
    }

    private function getTotalTreeCount()
    {
        return Tree::count();
    }

    private function getTreesWithCarbonStats()
    {
        return Tree::with('harvests')->get()->map(function ($tree) {
            return $this->addCarbonStats($tree);
        });
    }

    private function getChartData()
    {
        return $this->getTreesWithCarbonStats()
            ->map(function ($tree) {
                return [
                    'code' => $tree->code ?? 'Tree ' . $tree->id,
                    'sequestration' => $tree->annual_sequestration_kg ?? 0,
                ];
            })
            ->toArray();
    }

    private function addCarbonStats($tree)
    {
        $biomass = $this->calculateBiomass($tree->stem_diameter, $tree->height);
        $carbonStock = $this->calculateCarbonStock($biomass);
        $annualSequestration = $this->calculateAnnualSequestration($carbonStock);

        $tree->estimated_biomass_kg = round($biomass, 2);
        $tree->carbon_stock_kg = round($carbonStock, 2);
        $tree->annual_sequestration_kg = round($annualSequestration, 2);

        return $tree;
    }

    private function calculateBiomass($stemDiameter, $height)
    {
        return 0.25 * pow($stemDiameter, 2) * $height;
    }

    private function calculateCarbonStock($biomass)
    {
        return $biomass * 0.5;
    }

    private function calculateAnnualSequestration($carbonStock)
    {
        return $carbonStock * 0.07;
    }
}