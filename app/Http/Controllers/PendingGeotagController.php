<?php

namespace App\Http\Controllers;

use App\PendingGeotag;
use App\Tree;
use Illuminate\Http\Request;

class PendingGeotagController extends Controller
{
    // Show all pending requests
    public function index()
    {
        $pending = PendingGeotag::where('status', 'pending')->get();
        return view('geotags.pending', compact('pending'));
    }

    // Approve request → move to trees
    public function approve($id)
    {
        $geotag = PendingGeotag::findOrFail($id);

        Tree::create([
            'code' => strtoupper($geotag->code),
            'type' => $geotag->type ??'Unknown',
            'age' => $geotag->age ?? 0,
            'height' => $geotag->height ?? 0,
            'stem_diameter' => $geotag->stem_diameter ?? 0,
            'canopy_diameter' => $geotag->canopy_diameter ?? 0,
            'latitude' => $geotag->latitude,
            'longitude' => $geotag->longitude,
        ]);

        $geotag->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Geotag approved and added to Trees!');
    }

    // Reject request
    public function reject($id)
    {
        $geotag = PendingGeotag::findOrFail($id);
        $geotag->update(['status' => 'rejected']);

        return redirect()->back()->with('error', 'Geotag rejected.');
    }
}
