<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'message',
        'is_read',
    ];

    /**
     * Each notification belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
