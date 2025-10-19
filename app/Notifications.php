<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $fillable = ['user_id', 'message', 'is_read', 'created_at', 'updated_at'];
}
