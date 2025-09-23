<?php

namespace App\Services;

use App\PendingGeotag;
use App\Tree;
use App\Notifications\GeotagStatusChanged;

class GeotagApprovalService
{
    public function approveGeotag($geotagId)
    {
        $geotag = $this->findPendingGeotag($geotagId);

        $tree = $this->createTreeFromGeotag($geotag);

        $this->markGeotagAsApproved($geotag);

        $geotag->user->notify(new GeotagStatusChanged('approved', $geotag->id));

        return $tree;
    }

    public function rejectGeotag($geotagId, $reason = null)
    {
        $geotag = $this->findPendingGeotag($geotagId);

        $this->markGeotagAsRejected($geotag, $reason);
        
        $geotag->user->notify(new GeotagStatusChanged('rejected', $geotag->id));
        
        return $this->markGeotagAsRejected($geotag, $reason);
    }

    private function findPendingGeotag($geotagId)
    {
        return PendingGeotag::where('id', $geotagId)
                            ->where('status', 'pending')
                            ->firstOrFail();
    }

    private function createTreeFromGeotag(PendingGeotag $geotag)
    {
        return Tree::create([
            'code' => $geotag->code,
            'type' => $geotag->type,
            'age' => $geotag->age,
            'height' => $geotag->height,
            'stem_diameter' => $geotag->stem_diameter,
            'canopy_diameter' => $geotag->canopy_diameter,
            'latitude' => $geotag->latitude,
            'longitude' => $geotag->longitude,
            'geotag_id' => $geotag->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function markGeotagAsApproved(PendingGeotag $geotag)
    {
        return $geotag->update([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);
    }

    private function markGeotagAsRejected(PendingGeotag $geotag, $reason = null)
    {
        return $geotag->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'rejection_reason' => $reason,
        ]);
    }
}
