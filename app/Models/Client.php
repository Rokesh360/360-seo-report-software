<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function seoReports(): HasMany
    {
        return $this->hasMany(SeoReport::class);
    }

    public function socialMediaReports(): HasMany
    {
        return $this->hasMany(SocialMediaReport::class);
    }

    public function assignServices(): HasMany
    {
        return $this->hasMany(AssignService::class);
    }
}
