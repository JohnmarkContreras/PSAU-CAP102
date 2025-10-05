<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\TreeImage;
use App\Models\TreeCode;
use App\Models\TreesData;
use Illuminate\Support\Facades\Auth;

class MobileGeotagController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'age' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'dbh' => 'nullable|numeric',
            'stem_diameter' => 'nullable|numeric',
            'canopy_diameter' => 'nullable|numeric',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'required|string', // base64
        ]);

        // 1️⃣ Save image
        $imageData = base64_decode($validated['image']);
        $filename = 'trees/' . Str::uuid() . '.jpg';
        Storage::disk('public')->put($filename, $imageData);

        $treeImage = TreeImage::create([
            'filename' => $filename,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => 0, // optional
            'taken_at' => now(),
            'source_type' => 'mobile-app',
        ]);

        // 2️⃣ Save tree code
        $treeCode = TreeCode::create([
            'tree_type_id' => null, // optional
            'tree_image_id' => $treeImage->id,
            'code' => $validated['code'],
            'created_by' => Auth::id() ?? 0,
        ]);

        // 3️⃣ Save tree measurements
        $treeData = TreesData::create([
            'tree_code_id' => $treeCode->id,
            'dbh' => $validated['dbh'] ?? null,
            'height' => $validated['height'] ?? null,
            'age' => $validated['age'] ?? null,
            'stem_diameter' => $validated['stem_diameter'] ?? null,
            'canopy_diameter' => $validated['canopy_diameter'] ?? null,
            'created_by' => Auth::id() ?? 0,
        ]);

        return response()->json([
            'message' => 'Geotag saved successfully',
            'tree_data_id' => $treeData->id,
            'tree_code_id' => $treeCode->id,
            'tree_image_id' => $treeImage->id,
            'image_url' => Storage::url($filename),
        ]);
    }
}
