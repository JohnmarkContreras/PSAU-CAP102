<?php

namespace App\Http\Controllers;

use App\PendingGeotag;
use App\Services\GeotagApprovalService;
use Illuminate\Http\Request;
use App\Notifications\GeotagStatusChanged;
use App\User;
class PendingGeotagController extends Controller
{
    private $approvalService;

    public function __construct(GeotagApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    // Show all pending geotags
    public function pending()
    {
        $pending = PendingGeotag::with(['user', 'processor'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('geotags.pending', compact('pending'));
    }

    // Approve a geotag and create a Tree
    public function approve($id)
    {
        $this->approvalService->approveGeotag($id);

        return redirect()->back()->with('success', 'Geotag approved and added to Trees!');
    }

    public function reject($id, Request $request)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $this->approvalService->rejectGeotag($id, $request->input('rejection_reason'));

        return redirect()->back()->with('status', 'Geotag rejected successfully.');
    }


    // View approved and rejected geotags (audit trail)
    public function history()
    {
        $geotags = PendingGeotag::with(['user', 'processor'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('code', 'desc')
            ->paginate(15);

        return view('geotags.history', compact('geotags'));
    }

    public function mobileMetadata()
    {
        return $this->hasOne(MobileGeotagMetadata::class);
    }
}
