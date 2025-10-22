<?php

namespace App\Services;

use App\PendingGeotagTree;
use App\TreeImage;
use App\TreeCode;
use App\TreeData;
use App\Notifications\GeotagStatusChanged;
use Illuminate\Support\Facades\Storage;
use App\User;
class GeotagApprovalService
{
    public function approve($geotagId): ?TreeData
{
    \Log::info('Starting approval for geotag: ' . $geotagId);

    $geotag = PendingGeotagTree::findOrFail($geotagId);
    \Log::info('Geotag found', ['code' => $geotag->code]);

    // Check for duplicate TreeCode
    $existingCode = TreeCode::where('code', $geotag->code)->first();
    if ($existingCode) {
         \Log::warning('Duplicate geotag code detected: ' . $geotag->code);
            $existingTreeData = TreeData::where('tree_code_id', $existingCode->id)
                ->orderBy('created_at', 'desc')
                ->first();

        if ($existingTreeData) {
            $existingTreeData->update([
                'dbh'             => $geotag->dbh,
                'height'          => $geotag->height,
                'age'             => $geotag->age,
                'stem_diameter'   => $geotag->stem_diameter,
                'canopy_diameter' => $geotag->canopy_diameter,
            ]);

            $params = [];
            if ($existingTreeData->treeCode && isset($existingTreeData->treeCode->alpha)) {
                $params['alpha'] = (float) $existingTreeData->treeCode->alpha;
            }
            $existingTreeData->computeAndSaveCarbon($params, true);
            $existingTreeData->refresh();
            app(\App\Services\CarbonTrackingService::class)->updateCarbonForTreeData($existingTreeData, $params);

            //  Mark geotag approved
            $this->markGeotagAsApproved($geotag);

            if ($geotag->user) {
                $geotag->user->notify(new GeotagStatusChanged('approved', $geotag->id));
            }
            //Return early — this is a duplicate update, not a new insert
            throw new \Exception('Geotag code "' . $geotag->code . '" already exists. The record was updated instead of inserted.');
            //return $existingTreeData;
        }

        $this->markGeotagAsApproved($geotag);
        // In case no TreeData was found (edge case)
        throw new \Exception('Geotag code "' . $geotag->code . '" already exists.');
        //return null;
    }

    try {
        // Move image
        $oldPath = $geotag->image_path;
        $filename = basename($oldPath);
        $newPath = 'tree_images/' . $filename;

        if (Storage::disk('public')->exists($oldPath) && $oldPath !== $newPath) {
            Storage::disk('public')->move($oldPath, $newPath);
            \Log::info('Image moved', ['from' => $oldPath, 'to' => $newPath]);
        }

        // Create TreeImage
        $treeImage = TreeImage::create([
            'latitude'   => $geotag->latitude,
            'longitude'  => $geotag->longitude,
            'filename'   => $filename,
            'taken_at'   => $geotag->taken_at ?? now(),
            'created_by' => auth()->id(),
        ]);

        // Create TreeCode
        $treeCode = TreeCode::create([
            'code'         => $geotag->code,
            'tree_image_id'=> $treeImage->id,
            'tree_type_id' => $geotag->tree_type_id,
            'created_by'   => auth()->id(),
        ]);

        // Create TreeData
        $treeData = TreeData::create([
            'tree_code_id'    => $treeCode->id,
            'dbh'             => $geotag->dbh,
            'height'          => $geotag->height,
            'age'             => $geotag->age,
            'stem_diameter'   => $geotag->stem_diameter,
            'canopy_diameter' => $geotag->canopy_diameter,
            'created_by'      => auth()->id(),
        ]);

        // Compute carbon
        $params = [];
        if ($treeData->treeCode && isset($treeData->treeCode->alpha)) {
            $params['alpha'] = (float) $treeData->treeCode->alpha;
        }
        $treeData->computeAndSaveCarbon($params, true);
        $treeData->refresh();

        app(\App\Services\CarbonTrackingService::class)->updateCarbonForTreeData($treeData, $params);

        //Mark geotag approved and update its image_path
        $this->markGeotagAsApproved($geotag, $newPath);

        if ($geotag->user) {
            $geotag->user->notify(new GeotagStatusChanged('approved', $geotag->id));
        }

        return $treeData;

    } catch (\Exception $e) {
        \Log::error('Approval process failed', [
            'geotag_id' => $geotagId,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        throw $e;
    }
}

    public function rejectGeotag($geotagId, $reason = null)
    {
        $geotag = $this->findPendingGeotag($geotagId);

        $this->markGeotagAsRejected($geotag, $reason);

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

    private function markGeotagAsApproved(PendingGeotagTree $geotag, $finalImagePath = null)
    {
        $geotag->status = 'approved';
        $geotag->updated_at = now();
        $geotag->processed_at = now();
        $geotag->processed_by = auth()->id();

        // Update or clear image_path so it doesn’t point to a moved file
        $geotag->image_path = $finalImagePath ?? null;

        $geotag->save();

        \Log::info('Geotag marked as approved', $geotag->toArray());
    }
    
    private function markGeotagAsRejected(PendingGeotagTree $geotag, $reason = null)
    {
        $geotag->status = 'rejected';
        $geotag->updated_at = now();
        $geotag->rejection_reason = $reason;
        $geotag->processed_at = now();
        $geotag->processed_by = auth()->id();
        $geotag->save();

        \Log::info('Geotag marked as rejected', $geotag->toArray());
    }
}