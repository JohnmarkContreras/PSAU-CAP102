<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * These must remain readable by the browser / JS runtime.
     */
    protected $except = [
        'XSRF-TOKEN',   // <-- critical for Sanctum CSRF
        // do NOT add your session cookie here
    ];
}