<?php

namespace App\Http\Controllers;

use App\Services\TreeStatsService;

class UserDashboardController extends Controller
{
    private $treeStatsService;

    public function __construct(TreeStatsService $treeStatsService)
    {
        $this->treeStatsService = $treeStatsService;
    }

    public function index()
    {
        $stats = $this->treeStatsService->getDashboardStats();
        $unreadCount = auth()->user()->unreadNotifications()->count();
        return view('pages.dashboard', array_merge($stats, [
            'role' => 'user',
            'unreadCount' => $unreadCount,
        ]));
    }

    public function farmData()
    {
        return view('pages.farm-data', ['role' => 'user']);
    }
    
    public function analytics()
    {
        return view('pages.analytics', ['role' => 'user']);
    }

    public function harvestManagement()
    {
        return view('pages.harvest-management', ['role' => 'user']);
    }
}