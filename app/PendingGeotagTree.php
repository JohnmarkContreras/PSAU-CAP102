<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingGeotagTree extends Model
{
    protected $fillable = ['image_path', 'thumb_path', 'latitude', 'longitude', 'code', 'taken_at', 'user_id', 'status', 'processed_at', 'processed_by', 'rejection_reason', 'tree_type_id'];

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
    
    public function mobileMetadata()
    {
        return $this->hasOne(MobileGeotagMetadata::class);
    }
    
    // Relationship to TreeType
    public function treeType()
    {
        return $this->belongsTo(TreeType::class);
    }
}