<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameJoinedUsers extends Model
{
    protected $fillable = ['user_id', 'game_id', 'keyword', 'is_joined'];
}
