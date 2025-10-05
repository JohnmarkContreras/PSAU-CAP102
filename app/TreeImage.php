<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TreeImage extends Model
    {
        protected $fillable = [
        'filename', 'latitude', 'longitude', 'accuracy', 'taken_at', 'source_type'
    ];

    public function code()
    {
        return $this->hasOne(TreeCode::class);
    }

}
