<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assign_service_id',

        // WhatsApp
        'total_conversations',
        'whatsapp_clicks',
        'whatsapp_ctr',
        'reach',
        'impressions',
        'frequency',

        // Lead generation
        'total_leads',
        'link_clicks',
        'lead_reach',
        'lead_impressions',
        'lead_frequency',
        'lead_ctr',

        // Engagement
        'total_engagement',
        'reactions',
        'comments',
        'shares',
        'saves',
        'profile_visits',
        'new_followers',

        'campaign_results',
        'creative_performance',
        'audience_performance',
        'top_posts',
        'content_analysis',
    ];

    protected $casts = [
        'assign_service_id'      => 'integer',

        'whatsapp_ctr'           => 'float',
        'lead_frequency'         => 'float',
        'lead_ctr'               => 'float',

        'campaign_results'       => 'array',
        'creative_performance'   => 'array',
        'audience_performance'   => 'array',
        'top_posts'              => 'array',
        'content_analysis'       => 'array',
    ];

    public function scopeForAssignService(Builder $query, int $assignServiceId): Builder
    {
        return $query->where('assign_service_id', $assignServiceId);
    }

    public function assignService(): BelongsTo
    {
        return $this->belongsTo(AssignService::class);
    }

    public function getServiceNamesAttribute(): string
    {
        return $this->assignService?->service_names ?? '';
    }

    public function getClientNamesAttribute(): string
    {
        return $this->assignService?->client?->name ?? '';
    }

    public function getReportTypeLabelAttribute(): string
    {
        return $this->service_names ?: 'Social Media Report';
    }
}