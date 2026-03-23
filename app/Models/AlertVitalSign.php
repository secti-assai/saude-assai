<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertVitalSign extends Model
{
    protected $fillable = ['triage_id', 'kind', 'severity', 'message'];
}
