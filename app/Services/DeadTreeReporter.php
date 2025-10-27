<?php

namespace App\Services;

use App\Tree;
use App\DeadTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\User;
class DeadTreeReporter
{
    public function report(Tree $tree, Request $request): void
    {
        // Update tree status
        $tree->status = 'dead';
        $tree->save();

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('dead_tree_images', 'public');
        }

        // Log dead tree report to request
                DeadTreeRequest::create([
            'tree_code' => $request->tree_code,
            'reason' => $request->reason,
            'image_path' => $imagePath,
            'submitted_by' => auth()->id(),
        ]);
    }
}
