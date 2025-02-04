<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $guarded = [];

    protected $casts = ['play_date' => 'datetime'];

    public function plays()
    {
        return $this->belongsTo(Play::class, 'play_id', 'id');
    }
}
