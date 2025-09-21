<?php

namespace App\Services;

use App\PendingGeotag;

class TreeGeolocationService
{
    public function createPendingGeotag(array $data)
    {
        return PendingGeotag::create([
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
    }
}