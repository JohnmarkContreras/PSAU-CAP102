<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        $role = 'superadmin';
        return view('pages.backup', compact('role'));
    }
}
