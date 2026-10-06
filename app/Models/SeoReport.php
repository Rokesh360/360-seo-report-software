<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoReport extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'on_page_items' => 'array',
        'keywords'      => 'array',
        'backlinks_items' => 'array',
        'images'        => 'array',
        'chart_data'         => 'array',
        'performance_images' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getReportingPeriodAttribute($value): ?string
    {
        if (! $value) return null;
        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) return $value;
        if (preg_match('/^([A-Za-z]+)\s+(\d{4})$/', $value, $m)) {
            return date('Y-m', strtotime("{$m[1]} 1 {$m[2]}"));
        }
        return $value;
    }
}
