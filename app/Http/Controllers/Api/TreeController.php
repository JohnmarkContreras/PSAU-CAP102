<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\TreeCode;

class TreeController extends Controller
{
    /**
     * List approved trees with optional filters and pagination.
     *
     * Query params supported:
     *   - tree_type_id (int)
     *   - since (ISO date string)
     *   - per_page (int, default 30)
     */
    public function index(Request $request)
{
    Log::info('TreeController index() hit', [
        'tree_type_id' => $request->query('tree_type_id'),
        'since'        => $request->query('since'),
        'per_page'     => $request->query('per_page'),
    ]);

    try {
        $query = TreeCode::with([
            'treeImage',
            'treeType',
            'treeData' => function ($q) {
                $q->latest('created_at')->limit(1); // latest measurement only
            }
        ])->where('status', 'approved')
          ->orderBy('updated_at', 'desc');

        // Filter by tree type
        if ($request->filled('tree_type_id')) {
            $query->where('tree_type_id', $request->tree_type_id);
        }

        // Incremental sync filter
        if ($request->filled('since')) {
            $query->where('updated_at', '>', $request->since);
        }

        // Paginate results
        $perPage = (int) $request->query('per_page', 30);
        $trees   = $query->paginate($perPage);

        // Add sync metadata (Laravel 7 safe)
        $first   = $trees->first();
        $meta = [
            'server_time'   => now()->toIso8601String(),
            'last_updated'  => $first ? optional($first->updated_at)->toIso8601String() : null,
            'total_records' => $trees->total(),
            'page'          => $trees->currentPage(),
            'per_page'      => $trees->perPage(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $trees->items(),
            'meta'    => $meta,
        ]);
    } catch (Throwable $e) {
        Log::error('TreeController index() failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch trees',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
}