<?php

namespace App\Helpers;

use App\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogHelper
{
    /**
     * Log an activity to the activity_log table.
     *
     * @param string $description
     * @param array $properties
     * @param string|null $logName
     * @param mixed|null $subject
     */
    public static function log(string $description, array $properties = [], ?string $logName = null, $subject = null): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'log_name'     => $logName,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject ? $subject->id : null,
            'causer_type'  => $user ? get_class($user) : null,
            'causer_id'    => $user ? $user->id : null,
            'properties'   => array_merge($properties, [
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
                'role'       => $user ? $user->role : null,
                'url'        => request()->fullUrl(),
                'method'     => request()->method(),
                'route'      => request()->route() ? request()->route()->getName() : null,
            ]),
        ]);
    }
}