<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameStatus extends Model
{
    protected $table = 'game_status';
    protected $fillable = ['name'];
}
