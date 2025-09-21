<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingGeotag extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'age',
        'height',
        'stem_diameter',
        'canopy_diameter',
        'latitude',
        'longitude',
        'status',
        'processed_at',    // Add this
        'processed_by',    // Add this
        'rejection_reason', // Add this
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'processed_at',     // Add this for automatic Carbon casting
    ];

    // Relationship to user who submitted the geotag
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to user who processed the geotag
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes for filtering
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}