<?php

namespace App\Services;

use App\Models\Tree;
use App\Models\DeadTree;

class TreeStatusService
{
    public function update(Tree $tree, array $data)
    {
        // Update tree status
        $tree->update(['status' => $data['status']]);

        // If status = dead, log into dead_trees
        if ($data['status'] === 'dead') {
            $imagePath = null;
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('dead_trees', 'public');
            }

            DeadTree::create([
                'tree_code'   => $tree->code,
                'reason'      => $data['reason'] ?? null,
                'image_path'  => $imagePath,
                'reported_at' => now(),
            ]);
        }
    }
}

