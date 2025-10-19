<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\PendingGeotagTree;
use Throwable;

class PendingGeotagTreeController extends Controller
{
    /**
     * GET /pending-trees
     */
    public function index()
    {
        Log::info('PendingTree index() hit');

        try {
            $trees = PendingGeotagTree::all();

            return response()->json([
                'success' => true,
                'data'    => $trees,
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree index() failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /pending-trees/{id}
     */
    public function show($id)
    {
        Log::info('PendingTree show() hit', ['id' => $id]);

        try {
            $tree = PendingGeotagTree::find($id);

            if (!$tree) {
                return response()->json(['message' => 'Tree not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $tree,
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree show() failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /pending-trees
     * (already provided in your snippet)
     */
    public function store(Request $request)
    {
        Log::info('PendingTree store() hit', [
            'user_id' => optional($request->user())->id,
            'has_file' => $request->hasFile('filename'),
            'all' => $request->except(['filename']),
        ]);

        try {
            $validated = $request->validate([
                'filename'         => 'required|image',
                'latitude'         => 'required|numeric',
                'longitude'        => 'required|numeric',
                'code'             => 'nullable|string',
                'dbh'              => 'nullable|numeric',
                'height'           => 'nullable|numeric',
                'age'              => 'nullable|numeric',
                'canopy_diameter'  => 'nullable|numeric',
                'tree_type_id'     => 'required|exists:tree_types,id',
                'taken_at'         => 'nullable|date',
            ]);

            if (! $request->hasFile('filename')) {
                return response()->json(['message' => 'No file uploaded'], 422);
            }

            $path = $request->file('filename')->store('pending_tree_images', 'public');
            if (!$path) {
                return response()->json(['message' => 'Failed to store image'], 500);
            }

            $pending = PendingGeotagTree::create([
                'image_path'      => $path,
                'latitude'        => $validated['latitude'],
                'longitude'       => $validated['longitude'],
                'code'            => $validated['code'] ?? null,
                'dbh'             => $validated['dbh'] ?? null,
                'height'          => $validated['height'] ?? null,
                'age'             => $validated['age'] ?? null,
                'canopy_diameter' => $validated['canopy_diameter'] ?? null,
                'taken_at'        => $validated['taken_at'] ?? null,
                'tree_type_id'    => $validated['tree_type_id'],
                'user_id'         => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tree added to pending trees successfully!',
                'pending' => $pending,
            ], 201);
        } catch (Throwable $e) {
            Log::error('PendingTree store() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /pending-trees/{id}
     */
    public function update(Request $request, $id)
    {
        Log::info('PendingTree update() hit', ['id' => $id]);

        try {
            $tree = PendingGeotagTree::find($id);
            if (!$tree) {
                return response()->json(['message' => 'Tree not found'], 404);
            }

            $validated = $request->validate([
                'latitude'        => 'sometimes|numeric',
                'longitude'       => 'sometimes|numeric',
                'code'            => 'nullable|string',
                'dbh'             => 'nullable|numeric',
                'height'          => 'nullable|numeric',
                'age'             => 'nullable|numeric',
                'canopy_diameter' => 'nullable|numeric',
                'tree_type_id'    => 'sometimes|exists:tree_types,id',
                'taken_at'        => 'nullable|date',
            ]);

            $tree->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tree updated successfully',
                'data'    => $tree,
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree update() failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /pending-trees/upload-image
     */
    public function uploadImage(Request $request)
    {
        Log::info('PendingTree uploadImage() hit');

        try {
            $validated = $request->validate([
                'tree_id' => 'required|exists:pending_geotag_trees,id',
                'image'   => 'required|image|max:2048',
            ]);

            $path = $request->file('image')->store('pending_tree_images', 'public');
            if (!$path) {
                return response()->json(['message' => 'Failed to store image'], 500);
            }

            $tree = PendingGeotagTree::find($validated['tree_id']);
            $tree->image_path = $path;
            $tree->save();

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data'    => $tree,
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree uploadImage() failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /pending-trees/sync-batch
     */
    public function syncBatch(Request $request)
    {
        Log::info('PendingTree syncBatch() hit');

        try {
            $validated = $request->validate([
                'trees' => 'required|array',
                'trees.*.latitude'        => 'required|numeric',
                'trees.*.longitude'       => 'required|numeric',
                'trees.*.tree_type_id'    => 'required|exists:tree_types,id',
                'trees.*.code'            => 'nullable|string',
                'trees.*.dbh'             => 'nullable|numeric',
                'trees.*.height'          => 'nullable|numeric',
                'trees.*.age'             => 'nullable|numeric',
                'trees.*.canopy_diameter' => 'nullable|numeric',
                'trees.*.taken_at'        => 'nullable|date',
            ]);

            $created = [];
            foreach ($validated['trees'] as $treeData) {
                $created[] = PendingGeotagTree::create($treeData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Batch sync completed',
                'data'    => $created,
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree syncBatch() failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /check-code
     */
        /**
     * POST /check-code
     */
    public function checkCode(Request $request)
    {
        Log::info('PendingTree checkCode() hit', ['code' => $request->input('code')]);

        try {
            $validated = $request->validate([
                'code' => 'required|string',
            ]);

            $exists = PendingGeotagTree::where('code', $validated['code'])->exists();

            return response()->json([
                'success' => $exists,
                'message' => $exists ? 'Code is valid' : 'Invalid code',
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree checkCode() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /pending-trees/{id}/approve
     */
    public function approve($id)
    {
        Log::info('PendingTree approve() hit', ['id' => $id]);

        try {
            $tree = PendingGeotagTree::find($id);

            if (!$tree) {
                return response()->json(['message' => 'Tree not found'], 404);
            }

            $tree->status = 'approved';
            $tree->approved_at = now();
            $tree->save();

            return response()->json([
                'success' => true,
                'message' => 'Tree approved successfully',
                'data'    => $tree,
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree approve() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /pending-trees/{id}/reject
     */
    public function reject($id, Request $request)
    {
        Log::info('PendingTree reject() hit', [
            'id' => $id,
            'reason' => $request->input('reason'),
        ]);

        try {
            $tree = PendingGeotagTree::find($id);

            if (!$tree) {
                return response()->json(['message' => 'Tree not found'], 404);
            }

            $tree->status = 'rejected';
            $tree->rejected_at = now();
            $tree->rejection_reason = $request->input('reason');
            $tree->save();

            return response()->json([
                'success' => true,
                'message' => 'Tree rejected successfully',
                'data'    => $tree,
            ]);
        } catch (Throwable $e) {
            Log::error('PendingTree reject() failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}