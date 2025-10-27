<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreeImageFilterRequest;
use App\Services\TreeImageService;
use Illuminate\Http\Request;
use App\TreeCode;
use App\TreeImage;
use App\TreeType;
use App\User;
class TreeImageController extends Controller
{
    protected $service;

    public function __construct(TreeImageService $service)
    {
        $this->service = $service;
    }

    public function index(TreeImageFilterRequest $request)
    {
        $trees = $this->service->getTreeData($request->validated());
        return view('trees.map_images', compact('trees'));
    }

    public function data(\Illuminate\Http\Request $request)
    {
        $filters = $request->only(['south','west','north','east','limit']);
        $treeImages = $this->service->getTreeData($filters);
        
        // Map the response to include TreeData id
        $mappedData = collect($treeImages)->map(function($image) {
            $treeDataId = null;
            
            // Get TreeCode by id (tree_code_id from service output)
            if ($image['tree_code_id']) {
                $treeCode = \App\TreeCode::find($image['tree_code_id']);
                
                if ($treeCode) {
                    // Get TreeData by tree_code_id
                    $treeData = \App\TreeData::where('tree_code_id', $treeCode->id)->first();
                    
                    if ($treeData) {
                        $treeDataId = $treeData->id;
                    }
                }
            }
            
            $imagePath = null;
            if ($image['filename']) {
                $imagePath = '/storage/tree_images/' . $image['filename'];
            }
            
            return [
                'id' => $treeDataId,
                'tree_code_id' => $image['tree_code_id'],
                'code' => $image['code'],
                'latitude' => $image['latitude'],
                'longitude' => $image['longitude'],
                'filename' => $image['filename'],
                'image_path' => $imagePath,
                'taken_at' => $image['taken_at'],
                'harvests' => $image['harvests']
            ];
        })->toArray();
        
        return response()->json($mappedData);
    }

    //for displaying total number of tags
    public function getCodes()
    {
        return \App\TreeCode::pluck('code');
    }

    //for the entry of tamarind trees
    public function create()
    {
        $treeTypes = TreeType::all();
        return view('trees.manual_add', compact('treeTypes',));
}

    public function store(Request $request)
{
    $validated = $request->validate([
        'code' => 'required|string|max:255',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'filename' => 'nullable|image|max:4096',
    ]);

    $takenAt = null;
    $imagePath = null;

    if ($request->hasFile('filename')) {
        $image = $request->file('filename');
        $imagePath = $image->store('tree_images', 'public');

        try {
            $exif = @exif_read_data($image->getPathname());
            if (!empty($exif['DateTimeOriginal'])) {
                $takenAt = \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
            }
        } catch (\Exception $e) {
            // silently fail
        }
    }

    $takenAt = $takenAt ?? now();

    $tree = TreeImage::create([
        'latitude' => $validated['latitude'],
        'longitude' => $validated['longitude'],
        'filename' => $imagePath ? basename($imagePath) : null,
        'taken_at' => $takenAt,
    ]);

    TreeCode::create([
        'tree_image_id' => $tree->id,
        'code' => strtoupper($validated['code']),
    ]);

    return response()->json(['success' => true]);
}

}
