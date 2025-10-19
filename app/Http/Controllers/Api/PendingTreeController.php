<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\PendingGeotagTree;
use App\GeotagTree;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessTreeImage;
use Illuminate\Support\Facades\DB;

class PendingTreeController extends Controller
{
public function index(Request $r){
    $user = $r->user();
    if ($user->role === 'admin' || $user->role === 'superadmin') {
    return PendingGeotagTree::where('status','pending')->paginate(30);
    }
    return PendingGeotagTree::where('user_id',$user->id)->paginate(30);
}

public function show($id, Request $r){
    $rec = PendingGeotagTree::findOrFail($id);
    $this->authorize('view', $rec);
    return $rec;
}

public function checkCode(Request $r){
    $r->validate(['code'=>'required|string']);
    $existing = PendingGeotagTree::where('code',$r->code)->first();
    if ($existing) return response()->json(['exists'=>true,'id'=>$existing->id,'status'=>$existing->status]);
    $approved = GeotagTree::where('code',$r->code)->first();
    if ($approved) return response()->json(['exists'=>true,'id'=>$approved->id,'status'=>'approved']);
    return response()->json(['exists'=>false]);
}

public function uploadImage(Request $r){
    $r->validate(['image'=>'required|image|max:15360']);
    $path = $r->file('image')->store('pending_images','public');
    return response()->json(['image_path'=>$path],201);
}

public function store(Request $r){
    $data = $r->validate([
    'code'=>'required|string|max:64|unique:pending_geotag_trees,code',
    'latitude'=>'required|numeric',
    'longitude'=>'required|numeric',
    'image_path'=>'nullable|string',
    'dbh'=>'nullable|numeric|min:0',
    'height'=>'nullable|numeric|min:0',
    'age'=>'nullable|integer|min:0',
    'canopy_diameter'=>'nullable|numeric|min:0',
    'notes'=>'nullable|string'
    ]);
    $data['user_id'] = $r->user()->id;
    try {
    $rec = PendingGeotagTree::create($data);
    if (!empty($data['image_path'])) ProcessTreeImage::dispatch($rec);
    return response()->json($rec,201);
    } catch (\Illuminate\Database\QueryException $e) {
    $existing = PendingGeotagTree::where('code',$data['code'])->first();
    return response()->json(['message'=>'Duplicate code','existing_id'=>$existing->id,'existing_status'=>$existing->status],409);
    }
}

public function update($id, Request $r){
    $rec = PendingGeotagTree::findOrFail($id);
    $this->authorize('update', $rec);
    $data = $r->validate([
    'latitude'=>'nullable|numeric',
    'longitude'=>'nullable|numeric',
    'image_path'=>'nullable|string',
    'dbh'=>'nullable|numeric|min:0',
    'height'=>'nullable|numeric|min:0',
    'age'=>'nullable|integer|min:0',
    'canopy_diameter'=>'nullable|numeric|min:0',
    'notes'=>'nullable|string'
    ]);
    $rec->update($data);
    if (!empty($data['image_path'])) ProcessTreeImage::dispatch($rec);
    return response()->json($rec,200);
}

public function syncBatch(Request $r){
    $items = $r->input('items',[]);
    $results = [];
    foreach ($items as $item) {
    $validator = Validator::make($item, [
        'code'=>'required|string|max:64|unique:pending_geotag_trees,code',
        'latitude'=>'required|numeric',
        'longitude'=>'required|numeric',
    ]);
    if ($validator->fails()) {
        $results[] = ['code'=>$item['code'] ?? null,'status'=>'error','message'=>$validator->errors()->first()];
        continue;
    }
    try {
        $item['user_id'] = $r->user()->id;
        $rec = PendingGeotagTree::create($item);
        if (!empty($item['image_path'])) ProcessTreeImage::dispatch($rec);
        $results[] = ['code'=>$rec->code,'status'=>'created','id'=>$rec->id];
    } catch (\Exception $e) {
        $results[] = ['code'=>$item['code'],'status'=>'error','message'=>$e->getMessage()];
    }
    }
    return response()->json($results,200);
}

// APPROVAL: move data into the three tables you indicated after approval
public function approve($id, Request $r){
    $this->authorize('approve', PendingGeotagTree::class);
    $rec = PendingGeotagTree::findOrFail($id);
    DB::transaction(function() use ($rec, $r) {
    // Create tree image record (table: tree_images)
    $treeImageId = DB::table('tree_images')->insertGetId([
        'path' => $rec->image_path,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Choose tree_type_id mapping strategy (example: default type 1 or map by payload)
    $treeTypeId = $rec->tree_type_id ?? 1;

    // Create tree_code (table: tree_code)
    $treeCodeId = DB::table('tree_code')->insertGetId([
        'code' => $rec->code,
        'tree_type_id' => $treeTypeId,
        'tree_image_id' => $treeImageId,
        'created_by' => $r->user()->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Create geotag_trees (core geotag record)
    $geoId = DB::table('geotag_trees')->insertGetId([
        'code' => $rec->code,
        'tree_code_id' => $treeCodeId,
        'user_id' => $rec->user_id,
        'latitude' => $rec->latitude,
        'longitude' => $rec->longitude,
        'dbh' => $rec->dbh,
        'height' => $rec->height,
        'age' => $rec->age,
        'canopy_diameter' => $rec->canopy_diameter,
        'image_id' => $treeImageId,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $rec->update(['status'=>'approved','approved_by'=>$r->user()->id,'approved_at'=>now()]);
    });
    return response()->json(['status'=>'approved'],200);
}

public function reject($id, Request $r){
    $this->authorize('approve', PendingGeotagTree::class);
    $r->validate(['reason'=>'nullable|string']);
    $rec = PendingGeotagTree::findOrFail($id);
    $rec->update(['status'=>'rejected','rejected_reason'=>$r->reason,'rejected_by'=>$r->user()->id,'rejected_at'=>now()]);
    return response()->json(['status'=>'rejected'],200);
}
}