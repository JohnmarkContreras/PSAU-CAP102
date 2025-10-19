<?php

namespace App\Http\Controllers\Voyager;

use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerRoleController as BaseVoyagerRoleController;
use Spatie\Permission\Models\Permission;

class RoleController extends BaseVoyagerRoleController
{
    public function update(Request $request, $id)
    {
        $role = \App\VoyagerRole::findOrFail($id);

        // Update basic role fields
        $role->name = $request->name;
        $role->display_name = $request->display_name;
        $role->guard_name = $request->guard_name ?? 'web';
        $role->save();

        // Sync Spatie permissions
        $permissions = $request->input('permissions', []);
        $role->syncPermissions($permissions);

        return redirect()->route('voyager.roles.index')->with([
            'message'    => 'Role updated successfully!',
            'alert-type' => 'success',
        ]);
    }
}
