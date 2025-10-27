<?php

namespace App\Http\Controllers\Voyager;

use TCG\Voyager\Http\Controllers\VoyagerBreadController;

class CustomBreadController extends VoyagerBreadController
{
    // Override addBread and updateBread to disable Voyager permission syncing
    public function storeBread(\Illuminate\Http\Request $request)
    {
        $result = parent::storeBread($request);
        // Skip Voyager permission creation — we handle permissions via Spatie
        return $result;
    }

    public function updateBread(\Illuminate\Http\Request $request, $id)
    {
        $result = parent::updateBread($request, $id);
        // Skip Voyager permission creation — we handle permissions via Spatie
        return $result;
    }
}
