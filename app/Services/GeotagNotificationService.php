<?php

namespace App\Services;

use App\PendingGeotag;
use App\Notifications\GeotagSubmittedNotification;
use Spatie\Permission\Models\Role;
use App\User;
class GeotagNotificationService
{
    public function notifyAdmins(PendingGeotag $geotag)
    {
        $roles = Role::whereIn('name', ['admin', 'superadmin'])->get();
        foreach ($roles as $role) {
            foreach ($role->users as $user) {
                $user->notify(new GeotagSubmittedNotification($geotag));
            }
        }
    }
}
