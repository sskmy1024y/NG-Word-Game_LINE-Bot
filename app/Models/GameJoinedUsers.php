<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameJoinedUsers extends Model
{
    protected $fillable = ['user_id', 'game_id', 'keyword_id', 'keyword_decide_user_id' , 'is_joined'];
    
    public function getUserData()
    {
        return $this->belongsTo('App\Models\LineFriend', 'user_id');
    }

    public function getDecideUserData()
    {
        return $this->belongsTo('App\Models\LineFriend', 'keyword_decide_user_id');
    }

    public function keyword()
    {
        return $this->belongsTo('App\Models\GameThemeKeyword');
    }
}
