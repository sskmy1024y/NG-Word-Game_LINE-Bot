<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameJoinedUsers extends Model
{
    protected $fillable = ['user_id', 'game_id', 'keyword', 'is_joined'];
    
    public function decide_user()
    {
        return $this->hasOne('App\Model\LineFriend');
    }
}
