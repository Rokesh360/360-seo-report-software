<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class SocialMediaReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assign_service_ids',
        'platforms',
        'campaign_objective',
        'campaign_overview',
        'target_audience',
        'campaign_focus',
        'key_highlights',

        'total_conversations',
        'total_enquiries',
        'whatsapp_clicks',
        'total_leads',
        'form_submissions',
        'link_clicks',
        'total_engagement',
        'reactions',
        'comments',
        'shares',
        'saves',
        'profile_visits',
        'new_followers',
        'reach',
        'impressions',
        'frequency',

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
        'assign_service_ids'     => 'array',

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

    /**
     * Assign services attached to this report.
     */
    public function getAssignServicesAttribute(): Collection
    {
        $ids = is_array($this->assign_service_ids)
            ? array_filter($this->assign_service_ids)
            : [];

        if (empty($ids)) {
            return collect();
        }

        return AssignService::whereIn('id', $ids)->get();
    }

    public function getServiceNamesAttribute(): string
    {
        return $this->assign_services
            ->map(fn ($a) => $a->service_names)
            ->filter()
            ->implode(', ');
    }

    public function getClientNamesAttribute(): string
    {
        return $this->assign_services
            ->map(fn ($a) => $a->client?->name)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    public function getReportTypeLabelAttribute(): string
    {
        return $this->service_names ?: 'Social Media Report';
    }
}