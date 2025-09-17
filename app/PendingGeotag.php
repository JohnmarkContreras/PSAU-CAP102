<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingGeotag extends Model
{

    protected $fillable = [
        'user_id',
        'code',
        'type',
        'latitude',
        'longitude',
        'age',
        'height',
        'stem_diameter',
        'canopy_diameter',
        'status',
    ];

    // Optional: relation to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
