<?php

namespace App;

use TCG\Voyager\Models\Role as VoyagerBaseRole;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Models\Permission;

class VoyagerRole extends VoyagerBaseRole
{
    use HasPermissions;

    protected $table = 'roles';
    protected $guard_name = 'web';

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_has_permissions',
            'role_id',
            'permission_id'
        );
    }
}
