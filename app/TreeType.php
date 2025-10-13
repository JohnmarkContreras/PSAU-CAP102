<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TreeType extends Model
{
    protected $table = 'tree_types';
    public $timestamps = false; // if your table doesn’t have created_at/updated_at
}