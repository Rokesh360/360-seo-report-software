<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleAdsReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assign_service_id',
        'data',
        'overview_images',
    ];

    protected $casts = [
        'assign_service_id' => 'integer',
        'data' => 'array',
        'overview_images' => 'array',
    ];

    public function assignService(): BelongsTo
    {
        return $this->belongsTo(AssignService::class);
    }
}
