<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log'; // This overrides the default 'activity_logs'
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // Optional: if you're using relationships
    public function causer()
    {
        return $this->morphTo();
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function getRouteAttribute()
    {
        return $this->properties['route'] ?? null;
    }

    public function getUrlAttribute()
    {
        return $this->properties['url'] ?? null;
    }

    public function getIpAttribute()
    {
        return $this->properties['ip'] ?? null;
    }

    public function getRoleAttribute()
    {
        return $this->properties['role'] ?? null;
    }

}

