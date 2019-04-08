<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = ['group_id', 'status_id'];

    public function status()
    {
        return $this->belongsTo('App\Models\GameStatus');
    }
}
