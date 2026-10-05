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
        'performance_images' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}