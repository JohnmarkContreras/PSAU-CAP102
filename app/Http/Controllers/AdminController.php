<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tree;
use App\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
class AdminController extends Controller
{
    public function index()
    {
        $role = 'admin';
        $totaltrees = Tree::count();
        $totalsour = Tree::where('type', 'sour')->count();
        $totalsweet = Tree::where('type', 'sweet')->count();
        $totalsemi_sweet = Tree::where('type', 'semi_sweet')->count();
        return view('pages.dashboard', compact('role', 'totaltrees', 'totalsour', 'totalsweet', 'totalsemi_sweet'));
    }

    public function farmData()
    {
        $role = 'admin';
        return view('pages.farm-data', compact('role'));
    }

    public function analytics()
    {
        $role = 'admin';
        return view('pages.analytics', compact('role'));
    }

    public function harvestManagement()
    {
        $role = 'admin';
        return view('pages.harvest-management', compact('role'));
    }

    public function usertable()
    {
        $archives = DB::table('user_archives')->orderBy('archived_at', 'desc')->paginate(10);
        $users = User::where('account_id', '3')->paginate(50);
        $role = 'admin';
        return view('admin.user_table', compact('users', 'archives', 'role'));
    }
    // Show edit form
    public function editUser($id)
    {
        $users = User::where('account_id', '3')->paginate(50);
        $roles = Role::all();
        return view('admin.edit_user', compact('users', 'roles'))->with('id', $id);
    }
    // Handle form submission
        public function updateUser(Request $request, $id)
    {
        $user = \App\User::findOrFail($id);
        $request->validate([
            'role' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Update user status
        $user->status = $request->status;
        $user->save();

        // Update role (if using Spatie roles)
        if ($user->getRoleNames()->first() !== $request->role) {
            $user->syncRoles([$request->role]);
        }
        return redirect()->back()->with('success', 'User updated successfully!');
    }
}