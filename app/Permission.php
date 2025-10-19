<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // Add a virtual "key" attribute so Voyager can use it
    protected $appends = ['key'];

    public function getKeyAttribute()
    {
        // Voyager expects "key", but Spatie uses "name"
        return $this->attributes['name'] ?? null;
    }
}