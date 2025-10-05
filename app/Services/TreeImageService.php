<?php

namespace App\Services;

use App\TreeImage;

class TreeImageService
{
    public function getTreeData(array $filters = [])
    {
        $query = TreeImage::with('code:id,tree_image_id,code')
            ->select(['id','latitude','longitude','filename','taken_at']);

        // Apply bounding box if provided
        if (isset($filters['south'],$filters['west'],$filters['north'],$filters['east'])) {
            $query->whereBetween('latitude', [$filters['south'], $filters['north']])
                ->whereBetween('longitude', [$filters['west'], $filters['east']]);
        }

        // Limit results per request
        $query->limit($filters['limit'] ?? 1000);

        return $query->get()->map(fn ($t) => [
            'id'        => $t->id,
            'code'      => $t->code->code ?? 'N/A',
            'latitude'  => $t->latitude,
            'longitude' => $t->longitude,
            'filename'  => $t->filename,
            'taken_at'  => $t->taken_at,
        ]);
    }
}
