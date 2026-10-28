<?php

namespace App\Support;

use App\Models\MetaAdsDailyStat;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class MetaAdsReportHelper
{
    public static function summarise(Carbon $from, Carbon $to): array
    {
        $rows = MetaAdsDailyStat::whereBetween('report_date', [$from->toDateString(), $to->toDateString()])->get();

        $campaigns = $rows->groupBy('campaign_id')->map(function ($group) {
            $totals = self::totals($group);
            $totals['name'] = $group->sortByDesc('report_date')->first()->campaign_name;
            $totals['type'] = self::guessType($totals);

            return $totals;
        })->sortByDesc('spend')->values()->all();

        $daily = $rows->groupBy(fn ($row) => $row->report_date->toDateString())
            ->map(fn ($group, $date) => ['date' => $date] + self::totals($group))
            ->sortKeys()->values()->all();

        return [
            'currency'       => $rows->first()->currency ?? 'INR',
            'days_with_data' => count($daily),
            'totals'         => self::totals($rows),
            'campaigns'      => $campaigns,
            'daily'          => $daily,
        ];
    }

    private static function totals(Collection $rows): array
    {
        $spend = round((float) $rows->sum('spend'), 2);
        $impressions = (int) $rows->sum('impressions');
        $clicks = (int) $rows->sum('clicks');
        $leads = (int) $rows->sum('leads');
        $conversations = (int) $rows->sum('messaging_conversations');

        // Reach is unique across days, so summing daily values would overcount people.
        return [
            'spend'                   => $spend,
            'impressions'             => $impressions,
            'clicks'                  => $clicks,
            'leads'                   => $leads,
            'messaging_conversations' => $conversations,
            'ctr_percent'             => $impressions ? round($clicks / $impressions * 100, 2) : null,
            'cpc'                     => $clicks ? round($spend / $clicks, 2) : null,
            'cpm'                     => $impressions ? round($spend / $impressions * 1000, 2) : null,
            'cost_per_lead'           => $leads ? round($spend / $leads, 2) : null,
            'cost_per_conversation'   => $conversations ? round($spend / $conversations, 2) : null,
        ];
    }

    private static function guessType(array $totals): string
    {
        if ($totals['leads'] > 0) {
            return 'lead generation';
        }

        if ($totals['messaging_conversations'] > 0) {
            return 'messaging / WhatsApp';
        }

        return 'awareness / engagement';
    }
}
