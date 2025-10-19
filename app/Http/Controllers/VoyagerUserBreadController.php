<?php
namespace App\Http\Controllers;
use TCG\Voyager\Http\Controllers\VoyagerUserController as BaseController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role as SpatieRole;

class VoyagerUserBreadController extends BaseController
{
    public function store(Request $request)
    {
        // map legacy role_id to account_id if account_id missing
        if ($request->has('role_id') && !$request->has('account_id')) {
            $request->merge(['account_id' => $request->input('role_id')]);
            $request->request->remove('role_id');
        }

        // ensure status exists
        if (!$request->has('status') || $request->input('status') === null) {
            $request->merge(['status' => 'active']);
        }

        $response = parent::store($request);

        // assign Spatie roles if roles[] posted (convert IDs to names)
        if ($request->filled('roles')) {
            $user = null;
            if (is_array($response) && isset($response['data'])) {
                $user = $response['data'];
            } elseif (method_exists($response, 'original')) {
                $user = $response->original;
            }
            if ($user && method_exists($user, 'syncRoles')) {
                $roleIds = (array)$request->input('roles');
                $names = SpatieRole::whereIn('id', $roleIds)->pluck('name')->toArray();
                $user->syncRoles($names);
            }
        }

        return $response;
    }

    public function update(Request $request, $id)
    {
        if ($request->has('role_id') && !$request->has('account_id')) {
            $request->merge(['account_id' => $request->input('role_id')]);
            $request->request->remove('role_id');
        }
        if (!$request->has('status') || $request->input('status') === null) {
            $request->merge(['status' => 'active']);
        }

        $response = parent::update($request, $id);

        if ($request->filled('roles')) {
            $user = \App\Models\User::find($id);
            if ($user && method_exists($user, 'syncRoles')) {
                $roleIds = (array)$request->input('roles');
                $names = SpatieRole::whereIn('id', $roleIds)->pluck('name')->toArray();
                $user->syncRoles($names);
            }
        }

        return $response;
    }
}