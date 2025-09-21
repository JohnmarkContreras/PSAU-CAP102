<?php

namespace App\Services;

use App\Tree;

class TreeStatsService
{
    private const TREE_TYPES = ['sour', 'sweet', 'semi_sweet'];

    public function getDashboardStats()
    {
        return [
            'totaltrees' => $this->getTotalTreeCount(),
            'totalsour' => $this->getTreeCountByType('sour'),
            'totalsweet' => $this->getTreeCountByType('sweet'),
            'totalsemi_sweet' => $this->getTreeCountByType('semi_sweet'),
        ];
    }

    public function getTreeCountByType($type)
    {
        return Tree::where('type', $type)->count();
    }

    public function getTotalTreeCount()
    {
        return Tree::count();
    }

    public function getTreeTypeBreakdown()
    {
        return collect(self::TREE_TYPES)->mapWithKeys(function ($type) {
            return [$type => $this->getTreeCountByType($type)];
        })->toArray();
    }

    public function getTreeStatsForChart()
    {
        $breakdown = $this->getTreeTypeBreakdown();
        
        return [
            'labels' => array_keys($breakdown),
            'data' => array_values($breakdown),
            'total' => $this->getTotalTreeCount(),
        ];
    }
}