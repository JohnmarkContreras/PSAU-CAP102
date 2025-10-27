<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HarvestPrediction extends Model
{   
    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE    = 'done';

    protected $fillable = ['code', 'predicted_date', 'predicted_quantity', 'status', 'actual_quantity', 'harvest_id'];

    protected $table = 'harvest_predictions';

    public function treeCode()
    {
        return $this->belongsTo(TreeCode::class, 'code', 'code');
    }

    // scope for pending predictions
    public function scopePending($q)
    {
        return $q->where('status', self::STATUS_PENDING);
    }

        protected $casts = [
        'predicted_date' => 'datetime',
    ];
}
