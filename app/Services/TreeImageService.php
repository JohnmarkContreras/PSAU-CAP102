<?php

namespace App\Services;

use App\TreeImage;
use App\User;
use App\TreeData;

class TreeImageService
{
    public function getTreeData(array $filters = [])
    {
        $query = TreeImage::with([
            'code' => function($q) {
                $q->select(['id', 'tree_image_id', 'code']);
            },
            'code.harvests' => function($q) {
                $q->select(['id', 'code', 'harvest_date', 'harvest_weight_kg']);
            },
            'code.treeData' => function($q) {
                $q->select(['id', 'tree_code_id', 'dbh', 'height'])->latest('id')->limit(1);
            }
        ])->select(['id', 'latitude', 'longitude', 'filename', 'taken_at']);

        // Apply bounding box if provided
        if (isset($filters['south'],$filters['west'],$filters['north'],$filters['east'])) {
            $query->whereBetween('latitude', [$filters['south'], $filters['north']])
                ->whereBetween('longitude', [$filters['west'], $filters['east']]);
        }

        // Filter by code if provided
        if (isset($filters['code'])) {
            $query->whereHas('code', function($q) {
                $q->where('code', $filters['code']);
            });
        }

        $query->limit($filters['limit'] ?? 1000);

        return $query->get()->map(function ($t) {
            return [
                'id'            => $t->id,
                'code'          => $t->code ? $t->code->code : 'N/A',
                'tree_code_id'  => $t->code ? $t->code->id : null,
                // Get coordinates from tree_images via relationship
                'latitude'      => (float) $t->latitude,
                'longitude'     => (float) $t->longitude,
                'filename'      => $t->filename,
                'taken_at'      => $t->taken_at,
                'harvests'      => $t->code && $t->code->harvests
                    ? $t->code->harvests->map(function ($h) {
                        return [
                            'date'   => $h->harvest_date,
                            'weight' => $h->harvest_weight_kg,
                        ];
                    })->toArray()
                    : [],
            ];
        });
    }
}