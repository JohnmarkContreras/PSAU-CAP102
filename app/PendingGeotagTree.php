<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class PendingGeotagTree extends Model
{
    protected $fillable = ['status','image_path', 'thumb_path', 'latitude', 'longitude', 'code', 'dbh', 'height', 'age', 'canopy_diameter', 'taken_at', 'planted_at', 'planted_year_only', 'user_id', 'processed_at', 'processed_by', 'rejection_reason', 'tree_type_id'];

    public function getAgeAttribute()
    {
        $now = Carbon::now();

        if ($this->planted_at) {
            $planted = $this->planted_at instanceof Carbon
                ? $this->planted_at
                : Carbon::parse($this->planted_at);

            return $this->planted_year_only
                ? $now->year - $planted->year   // year-only flag set
                : $planted->diffInYears($now);  // full date, precise diff
        }

        if ($this->planted_year_only) {
            // If no planted_at but we have a year-only value stored
            return $now->year - (int) $this->planted_year_only;
        }

        return null;
    }
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