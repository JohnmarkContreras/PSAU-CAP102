<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
class UserAccountService
{
    private const ACCOUNT_ROLES = [
        '1' => 'superadmin',
        '2' => 'admin',
        '3' => 'user',
    ];

    public function createUser(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'account_id' => $data['account_id'],
        ]);

        $this->assignUserRole($user, $data['account_id']);

        return $user;
    }

    public function canDeleteUser($userId)
    {
        return auth()->id() != $userId;
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
    }

    private function assignUserRole(User $user, $accountId)
    {
        $roleName = $this->getRoleNameFromAccountId($accountId);
        
        $user->assignRole($roleName);
        
        $this->syncAccountIdWithRole($user, $roleName);
    }

    private function getRoleNameFromAccountId($accountId)
    {
        return self::ACCOUNT_ROLES[$accountId] ?? 'user';
    }

    private function syncAccountIdWithRole(User $user, $roleName)
    {
        $role = Role::where('name', $roleName)->first();
        
        if ($role) {
            $user->update(['account_id' => $role->id]);
        }
    }
}