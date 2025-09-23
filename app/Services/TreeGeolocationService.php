<?php

namespace App\Services;

use App\PendingGeotag;
use App\Notifications\TreeNotification;
use App\Services\GeotagNotificationService;

class TreeGeolocationService
{
    public function createPendingGeotag(array $data)
    {
        $geotag = PendingGeotag::create([
            'user_id' => auth()->id(),
            'code' => $data['code'],
            'type' => $data['type'],
            'age' => $data['age'],
            'height' => $data['height'],
            'stem_diameter' => $data['stem_diameter'],
            'canopy_diameter' => $data['canopy_diameter'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'status' => 'pending',
        ]);
        //notification
        app(GeotagNotificationService::class)->notifyAdmins($geotag);
        
        return $geotag;
    }
}