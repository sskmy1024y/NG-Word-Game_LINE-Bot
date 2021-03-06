<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LineFriend
 * @package App\Models
 *
 * @property int $id
 * @property string $line_id
 * @property string $display_name
 */
class LineGroup extends Model
{
    protected $fillable = ['line_group_id', 'display_name'];
}
