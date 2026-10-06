<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialMediaReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        //  No user_id, no client_name — client_id replaces both
        'client_id',
        'report_type',
        'reporting_period_start',
        'reporting_period_end',
        'platforms',
        'campaign_objective',
        'campaign_overview',
        'target_audience',
        'campaign_focus',
        'key_highlights',

        // WhatsApp Meta Ads
        'total_conversations',
        'total_enquiries',
        'whatsapp_clicks',

        // Lead Generation
        'total_leads',
        'form_submissions',
        'link_clicks',

        // Engagement
        'total_engagement',
        'reactions',
        'comments',
        'shares',
        'saves',
        'profile_visits',
        'new_followers',

        // Shared
        'reach',
        'impressions',
        'frequency',

        // JSON
        'campaign_results',
        'creative_performance',
        'audience_performance',
        'top_posts',
        'content_analysis',
        'performance_highlights',
        'key_observations',
        'monthly_summary',
        'next_month_plan',
    ];

    protected $casts = [
        'reporting_period_start' => 'date',
        'reporting_period_end'   => 'date',

        'campaign_results'       => 'array',
        'creative_performance'   => 'array',
        'audience_performance'   => 'array',
        'top_posts'              => 'array',
        'content_analysis'       => 'array',
        'performance_highlights' => 'array',
        'key_observations'       => 'array',
        'monthly_summary'        => 'array',
        'next_month_plan'        => 'array',
    ];

    /**  Client relation — replaces user() */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getReportTypeLabelAttribute(): string
    {
        return match ($this->report_type) {
            'whatsapp_meta_ads'       => 'WhatsApp Meta Ads Monthly Report',
            'lead_generation_ads'     => 'Lead Generation Ads Monthly Report',
            'social_media_engagement' => 'Social Media Engagement Monthly Report',
            'all'                     => 'All Reports (WhatsApp + Lead Gen + Engagement)',
            default                   => 'Social Media Report',
        };
    }

    /** Convenience accessor — blade can still use $report->client_name */
    public function getClientNameAttribute(): ?string
    {
        return $this->client?->name;
    }
}