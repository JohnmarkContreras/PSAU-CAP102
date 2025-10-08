<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PendingGeotagTree;
use App\Services\GeotagApprovalService;
use App\User;
use App\Notifications\GeotagStatusChanged;
class PendingGeotagTreeController extends Controller
{
    protected $approvalService;

    public function __construct(GeotagApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function store(Request $request)
    {
        logger()->info('Store PendingGeotagTree Request:', $request->all());
        $validated = $request->validate([
            'filename' => 'required|image',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'code' => 'required|string|unique:pending_geotag_trees,code',
            'tree_type_id' => 'required|exists:tree_types,id',
            'taken_at' => 'nullable|date',
        ]);

        $path = $request->file('filename')->store('pending_tree_images', 'public');

        $pending = PendingGeotagTree::create([
            'image_path' => $path,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'code' => $validated['code'],
            'taken_at' => $validated['taken_at'] ?? null,
            'tree_type_id' => $validated['tree_type_id'],
            'user_id' => auth()->id(), // attach user
        ]);

            // 🔔 Notify admins and superadmins
        $admins = User::role(['admin', 'superadmin'])->get(); // Spatie Role check
        if ($admins->count()) {
            foreach ($admins as $admin) {
                $admin->notify(new GeotagStatusChanged('pending', $pending->id));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tree added to pending trees successfully!',
            'pending' => $pending
        ]);
    }

    public function index(Request $request)
    {
        $pending = PendingGeotagTree::where('status', 'pending')->get();

        if ($request->ajax()) {
            return view('partials.table', compact('pending'))->render();
        }
        return view('pending_geotags.index', compact('pending'));
    }

    // Approve a geotag and create a Tree
    public function approve($id)
    {
        $this->approvalService->approveGeotag($id);

        return redirect()->back()->with('success', 'Geotag approved and added to Trees!');
    }

    // Reject a geotag with optional reason
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $this->approvalService->rejectGeotag($id, $request->input('rejection_reason'));

        return redirect()->back()->with('status', 'Geotag rejected successfully.');
    }
}
