<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineEventLogs extends Model
{
    protected $fillable = [
        'line_id',
        'event_type',
        'contents'
    ];
}
