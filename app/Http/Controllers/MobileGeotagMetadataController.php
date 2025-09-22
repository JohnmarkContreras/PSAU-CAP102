<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PendingGeotag;
use App\MobileGeotagMetadata;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileGeotagMetadataController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'age' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'stem_diameter' => 'nullable|numeric',
            'canopy_diameter' => 'nullable|numeric',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'required|string', // base64
            'device_id' => 'nullable|string',
        ]);

        // Decode and store image
        $imageData = base64_decode($validated['image']);
        $filename = 'trees/' . Str::uuid() . '.jpg';
        Storage::disk('public')->put($filename, $imageData);

        // Create pending geotag
        $pending = PendingGeotag::create([
            'code' => $validated['code'],
            'age' => $validated['age'] ?? null,
            'height' => $validated['height'] ?? null,
            'stem_diameter' => $validated['stem_diameter'] ?? null,
            'canopy_diameter' => $validated['canopy_diameter'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'type' => 'mobile',
            'user_id' => Auth::id(),
            'status' => 'pending',
        ]);

        // Create metadata
        MobileGeotagMetadata::create([
            'pending_geotag_id' => $pending->id,
            'tree_id' => null,
            'image' => $filename,
            'device_id' => $validated['device_id'] ?? null,
            'source' => 'mobile-react',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Mobile geotag stored and image decoded',
            'pending_id' => $pending->id,
            'image_url' => Storage::url($filename),
        ]);
    }
}
