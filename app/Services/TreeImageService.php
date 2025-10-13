<?php

namespace App\Services;

use App\TreeImage;

class TreeImageService
{
    public function getTreeData(array $filters = [])
    {
        $query = TreeImage::with([
            'code:id,tree_image_id,code',
            'code.harvests:id,code,harvest_date,harvest_weight_kg'
        ])->select(['id','latitude','longitude','filename','taken_at']);

        // Apply bounding box if provided
        if (isset($filters['south'],$filters['west'],$filters['north'],$filters['east'])) {
            $query->whereBetween('latitude', [$filters['south'], $filters['north']])
                ->whereBetween('longitude', [$filters['west'], $filters['east']]);
        }

        $query->limit($filters['limit'] ?? 1000);

        return $query->get()->map(function ($t) {
            return [
                'id'        => $t->id,
                'code'      => $t->code ? $t->code->code : 'N/A',
                'latitude'  => $t->latitude,
                'longitude' => $t->longitude,
                'filename'  => $t->filename,
                'taken_at'  => $t->taken_at,
                'harvests'  => $t->code
                    ? $t->code->harvests->map(function ($h) {
                        return [
                            'date'   => $h->harvest_date,
                            'weight' => $h->harvest_weight_kg,
                        ];
                    })
                    : [],
            ];
        });
    }
}
