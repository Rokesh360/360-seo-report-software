<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class AssignService extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assign_services';

    protected $fillable = [
        'client_id',
        'service_id',
        'from_date',
        'to_date',
        'title',
        'budget',
        'description',
        'status',
        'platforms',
        'campaign_objective',
        'campaign_overview',
        'target_audience',
        'campaign_focus',
        'key_highlights',
        'campaign_ids',
        'key_observations',
        'performance_highlights',
        'monthly_summary',
        'next_month_plan',
    ];

    protected $casts = [
        'from_date'  => 'date',
        'to_date'    => 'date',
        'service_id' => 'array',
        'key_observations' => 'array',
        'performance_highlights' => 'array',
        'monthly_summary' => 'array',
        'next_month_plan' => 'array',
        'campaign_ids' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(SocialMediaReport::class);
    }

    /* --------------------------------------------------------------
     |  Services (service_id is a JSON array of IDs)
     -------------------------------------------------------------- */

    /**
     * All Service models attached to this assignment.
     * Renamed from services() to avoid Eloquent treating it as a relation.
     */
    public function getServices(): Collection
    {
        $ids = is_array($this->service_id)
            ? array_filter($this->service_id)
            : array_filter([(int) $this->service_id]);

        if (empty($ids)) {
            return collect();
        }

        return Service::whereIn('id', $ids)->get();
    }

    /**
     * Backward-compat: `$assignService->service` returns the FIRST service.
     */
    public function getServiceAttribute(): ?Service
    {
        return $this->getServices()->first();
    }

    /**
     * Comma-separated list of service names.
     */
    public function getServiceNamesAttribute(): string
    {
        return $this->getServices()->pluck('name')->implode(', ');
    }

    public function getDurationAttribute(): string
    {
        return $this->from_date->format('d M Y') . ' - ' . $this->to_date->format('d M Y');
    }

    /* --------------------------------------------------------------
     |  Scopes
     -------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 1)->orWhereNull('status');
        });
    }

    public function scopeCoveringDate($query, $date)
    {
        return $query->where('from_date', '<=', $date)
            ->where('to_date', '>=', $date);
    }

    public function getReportTypesAttribute(): array
    {
        $map = [
            'whatsapp meta ads'       => 'whatsapp_meta_ads',
            'lead generation ads'     => 'lead_generation_ads',
            'social media engagement' => 'social_media_engagement',
        ];

        return $this->getServices()->pluck('name')
            ->map(function ($name) use ($map) {
                $n = \Illuminate\Support\Str::lower(trim($name));
                foreach ($map as $needle => $type) {
                    if (str_contains($n, $needle)) return $type;
                }
                return null;
            })->filter()->unique()->values()->all();
    }

    public const SOCIAL_REPORT_EXCLUDED_SERVICE_IDS = [4, 5];

    public function scopeForSocialReports($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('service_id')
                ->orWhere(function ($inner) {
                    foreach (self::SOCIAL_REPORT_EXCLUDED_SERVICE_IDS as $id) {
                        $inner->whereJsonDoesntContain('service_id', (int) $id)
                            ->whereJsonDoesntContain('service_id', (string) $id);
                    }
                });
        });
    }
}
