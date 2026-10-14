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
}