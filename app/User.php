<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use TCG\Voyager\Models\User as VoyagerUser;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\ArchivesModel;
use Illuminate\Database\Eloquent\Builder;

class User extends VoyagerUser
{
    use HasRoles, Notifiable, HasApiTokens, ArchivesModel;
    
    protected $guard_name = 'web';
    
    public function syncAccountId()
    {
        $role = $this->roles()->first();
        if ($role) {
            $this->account_id = $role->id;
            $this->saveQuietly();
        }
    }

    protected $fillable = [
        'id', 'name', 'email', 'password', 'account_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function hasPermission($permission)
    {
        if ($this->hasRole('superadmin')) {
            return true;
        }

        return $this->hasPermissionTo($permission);
    }
    
    public function getMorphClass()
    {
        return 'App\\User';
    }

    /**
     * Get unread notifications - return empty query to prevent N+1
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Override to prevent automatic loading
     */
    public function getUnreadNotificationsCount()
    {
        // Check if count was already loaded
        if ($this->relationLoaded('unread_notifications_count')) {
            return $this->getAttribute('unread_notifications_count') ?? 0;
        }
        
        // Return 0 - don't execute count queries
        return 0;
    }
}