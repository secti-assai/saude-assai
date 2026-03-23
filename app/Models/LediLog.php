<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LediLog extends Model
{
    protected $fillable = ['ledi_queue_id', 'status', 'response'];
}
