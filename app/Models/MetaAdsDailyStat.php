<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdsDailyStat extends Model
{
    protected $guarded = [];

    protected $casts = [
        'report_date' => 'date',
        'actions'     => 'array',
    ];
}