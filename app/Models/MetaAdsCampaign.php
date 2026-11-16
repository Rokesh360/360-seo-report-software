<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdsCampaign extends Model
{
    protected $guarded = [];

    protected $casts = [
        'first_report_date' => 'date',
        'last_report_date'  => 'date',
    ];

    public function dailyStats()
    {
        return $this->hasMany(MetaAdsDailyStat::class);
    }
}