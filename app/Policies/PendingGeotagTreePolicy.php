<?php
namespace App\Policies;
use App\User;
use App\PendingGeotagTree;

class PendingGeotagTreePolicy
{
public function view(User $user, PendingGeotagTree $rec){
    return $user->role === 'admin' || $user->id === $rec->user_id;
}
public function update(User $user, PendingGeotagTree $rec){
    return $user->role === 'admin' || ($user->id === $rec->user_id && $rec->status === 'pending');
}
public function approve(User $user){
    return in_array($user->role, ['admin','superadmin']);
}
}