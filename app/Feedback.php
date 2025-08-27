<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = ['user_id', 'type', 'rating', 'message', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
