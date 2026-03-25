<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    protected $fillable = ['user_id', 'channel', 'recipient', 'message', 'status', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
