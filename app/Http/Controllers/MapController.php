<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tree;
    
class MapController extends Controller
{
    public function showMap()
    {
        $trees = Tree::with('harvests')->get();
        return view('trees.map', compact('trees'));
    }
}
