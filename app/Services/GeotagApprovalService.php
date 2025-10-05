<?php

namespace App\Services;

use App\PendingGeotagTree;
use App\TreeImage;
use App\TreeCode;
use App\Notifications\GeotagStatusChanged;
use Illuminate\Support\Facades\Storage;

class GeotagApprovalService
{
    public function approveGeotag($geotagId)
    {
        $geotag = $this->findPendingGeotag($geotagId);

        // Prevent duplicate TreeCode (which means duplicate tree)
        $existingCode = TreeCode::where('code', $geotag->code)->first();
        if ($existingCode) {
            $this->markGeotagAsApproved($geotag); // Still mark as approved
            return TreeImage::find($existingCode->tree_image_id);
        }

        // Move image from pending folder to tree_images folder if not already there
        $oldPath = $geotag->image_path;
        $filename = basename($oldPath);
        $newPath = 'tree_images/' . $filename;

        if (Storage::disk('public')->exists($oldPath) && $oldPath !== $newPath) {
            Storage::disk('public')->move($oldPath, $newPath);
        }

        // Create TreeImage record (no code column here)
        $treeImage = TreeImage::create([
            'latitude' => $geotag->latitude,
            'longitude' => $geotag->longitude,
            'filename' => $filename,
            'taken_at' => $geotag->taken_at,
            'created_by' => auth()->id(),
        ]);

        // Create TreeCode record (code is here)
        TreeCode::create([
            'code' => $geotag->code,
            'tree_image_id' => $treeImage->id,
            'tree_type_id' => $geotag->tree_type_id,
            'created_by' => auth()->id(),
        ]);

        $this->markGeotagAsApproved($geotag);

        // Notify user if user_id exists and user relation is set up
        if ($geotag->user) {
            $geotag->user->notify(new GeotagStatusChanged('approved', $geotag->id));
        }

        return $treeImage;
    }
    // Reject a geotag with optional reason
    public function rejectGeotag($geotagId, $reason = null)
    {
        $geotag = $this->findPendingGeotag($geotagId);

        $this->markGeotagAsRejected($geotag, $reason);


        // When rejecting
        if ($geotag->user) {
            $geotag->user->notify(new GeotagStatusChanged('rejected', $geotag->id, $reason));
        }

        return $geotag;
    }

    private function findPendingGeotag($geotagId)
    {
        return PendingGeotagTree::where('id', $geotagId)
                            ->where('status', 'pending')
                            ->firstOrFail();
    }

    private function markGeotagAsApproved(PendingGeotagTree $geotag)
    {
        $geotag->status = 'approved';
        $geotag->updated_at = now();
        $geotag->processed_at = now();
        $geotag->processed_by = auth()->id();
        $geotag->save();
    }

    private function markGeotagAsRejected(PendingGeotagTree $geotag, $reason = null)
    {
    
        $geotag->status = 'rejected';
        $geotag->updated_at = now();
        $geotag->rejection_reason = $reason;
        $geotag->processed_at = now();
        $geotag->processed_by = auth()->id();
        $geotag->save();
    }
}
