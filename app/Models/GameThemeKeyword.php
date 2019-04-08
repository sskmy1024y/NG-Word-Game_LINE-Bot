<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameThemeKeyword extends Model
{
    protected $table = 'game_theme_keywords';
    protected $fillable = ["word"];
}
