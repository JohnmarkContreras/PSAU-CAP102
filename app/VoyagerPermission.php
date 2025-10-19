<?php

namespace App;

use TCG\Voyager\Models\Permission as VoyagerBasePermission;

class VoyagerPermission extends VoyagerBasePermission
{
    protected $table = 'voyager_permissions';
    protected $fillable = ['key', 'table_name'];
}
