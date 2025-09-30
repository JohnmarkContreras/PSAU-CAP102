<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tree;
use Illuminate\Support\Facades\Auth;

class MapController extends Controller
{
    public function showMap()
    {
        $trees = Auth::user()->hasAnyRole(['admin', 'superadmin'])
            ? Tree::all()
            : Tree::whereRaw("LOWER(status) != 'dead'")->get();
        return view('trees.map', compact('trees'));
    }
}
