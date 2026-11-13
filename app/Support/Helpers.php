<?php

namespace App\Support;

use App\Models\AssignService;
use App\Models\MetaAdsDailyStat;
use App\Models\SocialMediaReport;
use App\Models\Service;
use App\Models\GstMaster;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class Helpers
{
    public const PALETTE = ['#2f855a', '#3182ce', '#dd6b20', '#805ad5', '#d53f8c', '#319795'];
    public const DEADLINE_ALERT_DAYS = 2;
    public const LOW_BUDGET_LIMIT = 1500;

    /**
     * Clean campaign IDs based on selected service availability.
     */
    public static function cleanCampaigns(array $data): array
    {
        return self::campaignAvailabilityCallback(
            $data,
            function (array $data, bool $available): array {
                $data['campaign_ids'] = $available && ! empty($data['campaign_ids'])
                    ? array_values($data['campaign_ids'])
                    : null;

                return $data;
            }
        );
    }

    /**
     * Private campaign availability checker with callback.
     */
    private static function campaignAvailabilityCallback(
        array $data,
        callable $callback
    ): array {
        $available = Service::whereIn('id', $data['service_id'] ?? [])
            ->where('is_campaign_available', true)
            ->exists();

        return $callback($data, $available);
    }

    public static function deadlineRows(Collection $assignServices, ?int $clientId): Collection
    {
        $today = Carbon::today();

        return $assignServices
            ->filter(fn($a) => $a->to_date && (! $clientId || (int) $a->client_id === $clientId))
            ->map(function ($a) use ($today) {
                $to = Carbon::parse($a->to_date)->startOfDay();
                $daysLeft = (int) $today->diffInDays($to, false);

                if ($daysLeft < 0 || $daysLeft > self::DEADLINE_ALERT_DAYS) {
                    return null;
                }

                return [
                    'id' => $a->id,
                    'client' => $a->client?->name ?? 'Unknown client',
                    'service' => $a->title ?: 'Service #' . $a->id,
                    'from_date' => $a->from_date,
                    'to_date' => $to,
                    'days_left' => $daysLeft,
                    'budget' => (float) $a->budget,
                ];
            })
            ->filter()
            ->sortBy('days_left')
            ->values();
    }

    public static function resolveMonth(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('!Y-m', $value) : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    public static function reportsBetween(Carbon $from, Carbon $to, ?array $scopedIds): Collection
    {
        return SocialMediaReport::query()
            ->whereBetween('created_at', [$from->toDateString(), $to->toDateString()])
            ->when($scopedIds !== null, fn($query) => $query->whereIn('assign_service_id', $scopedIds))
            ->get([
                'id',
                'assign_service_id',
                'created_at',
                'reach',
                'impressions',
                'whatsapp_clicks',
                'link_clicks',
                'total_leads',
                'total_enquiries',
                'reactions',
                'comments',
                'shares',
                'saves',
                'campaign_results',
            ]);
    }

    public static function lowBudgetRows(Collection $assignServices, ?int $clientId, float $gstRate = 0.0): Collection
    {
        $assignments = $assignServices->filter(function ($a) use ($clientId) {
            return (! $clientId || (int) $a->client_id === $clientId)
                && ($a->status === null || (int) $a->status === 1)
                && ! empty($a->campaign_ids)
                && $a->from_date
                && $a->to_date;
        });

        if ($assignments->isEmpty()) {
            return collect();
        }

        $campaignIds = $assignments
            ->flatMap(fn($a) => $a->campaign_ids)
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values();

        // One query covering the widest date window, then split per assignment in memory.
        $stats = MetaAdsDailyStat::query()
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween('report_date', [
                $assignments->min(fn($a) => $a->from_date->toDateString()),
                $assignments->max(fn($a) => $a->to_date->toDateString()),
            ])
            ->get(['campaign_id', 'report_date', 'spend'])
            ->groupBy(fn($row) => (string) $row->campaign_id);

        return $assignments
            ->map(function ($a) use ($stats, $gstRate) {
                $from = $a->from_date->toDateString();
                $to   = $a->to_date->toDateString();

                // Only spend that falls inside this assignment's date range
                $inRange = collect($a->campaign_ids)
                    ->flatMap(fn($id) => $stats->get((string) $id, collect()))
                    ->filter(function ($row) use ($from, $to) {
                        $d = $row->report_date->toDateString();
                        return $d >= $from && $d <= $to;
                    });

                $budget    = (float) $a->budget;
                $spentExcl = (float) $inRange->sum('spend');
                $spentIncl = $spentExcl * (1 + $gstRate);
                $available = $budget - $spentIncl;

                if ($available > self::LOW_BUDGET_LIMIT) {
                    return null;
                }

                $lastDate = $inRange->max(fn($row) => $row->report_date->toDateString());

                return [
                    'id'             => $a->id,
                    'client'         => $a->client?->name ?? 'Unknown client',
                    'service'        => $a->title ?: 'Service #' . $a->id,
                    'from_date'      => $a->from_date,
                    'to_date'        => $a->to_date,
                    'date'           => $lastDate ? Carbon::parse($lastDate) : now(),
                    'budget'         => $budget,
                    'spent_excl_gst' => $spentExcl,
                    'spent'          => $spentIncl,   // incl. GST
                    'available'      => $available,   // budget − (spent + GST)
                ];
            })
            ->filter()
            ->sortBy('available')
            ->values();
    }

    public static function totals(Collection $reports): array
    {
        return [
            'reach' => (int) $reports->sum('reach'),
            'impressions' => (int) $reports->sum('impressions'),
            'clicks' => (int) $reports->sum('whatsapp_clicks') + (int) $reports->sum('link_clicks'),
            'leads' => (int) $reports->sum('total_leads') + (int) $reports->sum('total_enquiries'),
        ];
    }

    public static function dailySeries(Collection $reports, int $days): array
    {
        $series = [
            'reach' => array_fill(1, $days, 0),
            'impressions' => array_fill(1, $days, 0),
            'clicks' => array_fill(1, $days, 0),
            'leads' => array_fill(1, $days, 0),
        ];

        foreach ($reports as $report) {
            $day = $report->created_at->day;
            $series['reach'][$day] += (int) $report->reach;
            $series['impressions'][$day] += (int) $report->impressions;
            $series['clicks'][$day] += (int) $report->whatsapp_clicks + (int) $report->link_clicks;
            $series['leads'][$day] += (int) $report->total_leads + (int) $report->total_enquiries;
        }

        return $series;
    }

    public static function sparkHeights(array $daily, int $bars = 10): array
    {
        $values = array_values($daily);
        $n = count($values);
        $buckets = [];

        for ($i = 0; $i < $bars; $i++) {
            $from = (int) floor($i * $n / $bars);
            $to = (int) floor(($i + 1) * $n / $bars);
            $buckets[] = array_sum(array_slice($values, $from, max(1, $to - $from)));
        }

        $max = max($buckets);

        return array_map(fn($value) => $max > 0 ? max(8, (int) round($value / $max * 100)) : 8, $buckets);
    }

    public static function buildChart(array $clicks, array $impressions, Carbon $start): array
    {
        $days = count($clicks);
        $w = 760;
        $top = 18;
        $bottom = 180;
        $h = $bottom - $top;

        $impStep = self::niceStep(max($impressions) / 3);
        $impMax = $impStep * 3;
        $clkMax = max(max($clicks), 1);

        $impPts = $clkPts = [];
        foreach (range(1, $days) as $i) {
            $x = ($i - 1) / ($days - 1) * $w;
            $impPts[] = [$x, $bottom - ($impressions[$i] / $impMax) * $h];
            $clkPts[] = [$x, $bottom - ($clicks[$i] / $clkMax) * $h];
        }

        $clicksPath = self::smoothPath($clkPts);
        $peakDay = array_search(max($clicks), $clicks, true);

        $xLabels = collect([1, 6, 11, 16, 21, 26, $days])->unique()
            ->map(fn($day) => $start->copy()->day($day)->format('M d'))->all();

        return [
            'hasData' => array_sum($clicks) + array_sum($impressions) > 0,
            'impressionsPath' => self::smoothPath($impPts),
            'clicksPath' => $clicksPath,
            'areaPath' => $clicksPath ? $clicksPath . sprintf(' L%.1F %d L0 %d Z', $w, $bottom, $bottom) : '',
            'peak' => max($clicks) > 0 ? $clkPts[$peakDay - 1] : null,
            'peakClicks' => self::shortNumber(max($clicks)),
            'peakImpressions' => self::shortNumber(max($impressions)),
            'yLabels' => [
                self::shortNumber($impStep * 3),
                self::shortNumber($impStep * 2),
                self::shortNumber($impStep),
                '0',
            ],
            'xLabels' => $xLabels,
        ];
    }

    public static function buildDonut(array $input): array
    {
        $total = array_sum(array_column($input, 0));
        $r = 40;
        $circ = 2 * M_PI * $r;
        $offset = 0;
        $slices = [];

        foreach ($input as $label => [$value, $color]) {
            if ($value <= 0 || $total <= 0) {
                continue;
            }
            $len = $circ * $value / $total;
            $slices[] = [
                'label' => $label,
                'value' => $value,
                'color' => $color,
                'pct' => round($value / $total * 100, 1),
                'dash' => sprintf('%.2F %.2F', $len, $circ - $len),
                'offset' => sprintf('%.2F', -$offset),
            ];
            $offset += $len;
        }

        return ['r' => $r, 'total' => $total, 'totalLabel' => self::shortNumber($total), 'slices' => $slices];
    }

    public static function clientRows(Collection $assignServices, Collection $assignById, Collection $reports, Carbon $start, Carbon $end, ?int $clientId): Collection
    {
        $rows = [];

        foreach ($assignServices as $assignment) {
            if (! $assignment->client_id || ($clientId && $assignment->client_id !== $clientId)) {
                continue;
            }
            if ($assignment->from_date && $assignment->to_date && $assignment->from_date->lte($end) && $assignment->to_date->gte($start)) {
                $rows[$assignment->client_id] ??= self::blankRow($assignment->client?->name);
                $rows[$assignment->client_id]['services']++;
            }
        }

        foreach ($reports as $report) {
            $cid = $assignById[(int) $report->assign_service_id]->client_id ?? null;

            if (! $cid) {
                continue;
            }

            $rows[$cid] ??= self::blankRow($assignById->firstWhere('client_id', $cid)?->client?->name);
            $rows[$cid]['reports']++;
            $rows[$cid]['reach'] += (int) $report->reach;
            $rows[$cid]['clicks'] += (int) $report->whatsapp_clicks + (int) $report->link_clicks;
            $rows[$cid]['last'] = max($rows[$cid]['last'], $report->created_at->toDateString());
        }

        $max = max(array_merge([0], array_column($rows, 'reach')));

        return collect($rows)
            ->map(function ($row) use ($max) {
                $row['share'] = $max > 0 ? (int) round($row['reach'] / $max * 100) : 0;
                $row['reachLabel'] = self::shortNumber($row['reach']);
                $row['lastLabel'] = $row['last'] ? Carbon::parse($row['last'])->format('d M') : 'No entries';
                return $row;
            })
            ->sortByDesc('reach')->take(8)->values();
    }

    public static function shortNumber(int|float $number): string
    {
        $number = (float) $number;
        if ($number >= 1_000_000) {
            return rtrim(rtrim(number_format($number / 1_000_000, 1), '0'), '.') . 'M';
        }
        if ($number >= 1_000) {
            return rtrim(rtrim(number_format($number / 1_000, 1), '0'), '.') . 'K';
        }

        return number_format($number);
    }

    private static function smoothPath(array $points): string
    {
        if (count($points) < 2) {
            return '';
        }

        $path = sprintf('M%.1F %.1F', $points[0][0], $points[0][1]);
        for ($i = 1; $i < count($points); $i++) {
            $controlX = ($points[$i - 1][0] + $points[$i][0]) / 2;
            $path .= sprintf(
                ' C%.1F %.1F %.1F %.1F %.1F %.1F',
                $controlX,
                $points[$i - 1][1],
                $controlX,
                $points[$i][1],
                $points[$i][0],
                $points[$i][1]
            );
        }

        return $path;
    }

    private static function blankRow(?string $name): array
    {
        return ['name' => $name ?: 'Unknown client', 'services' => 0, 'reports' => 0, 'reach' => 0, 'clicks' => 0, 'last' => ''];
    }

    private static function niceStep(float $value): int
    {
        if ($value <= 0) {
            return 1;
        }
        $power = 10 ** floor(log10($value));
        foreach ([1, 2, 2.5, 5, 10] as $multiplier) {
            if ($value <= $multiplier * $power) {
                return (int) max(1, ceil($multiplier * $power));
            }
        }

        return (int) ceil(10 * $power);
    }

    public static function summariseMetaAds(Carbon $from, Carbon $to): array
    {
        $rows = MetaAdsDailyStat::whereBetween('report_date', [$from->toDateString(), $to->toDateString()])->get();

        $campaigns = $rows->groupBy('campaign_id')->map(function ($group) {
            $totals = self::metaTotals($group);
            $totals['name'] = $group->sortByDesc('report_date')->first()->campaign_name;
            $totals['type'] = self::guessMetaType($totals);

            return $totals;
        })->sortByDesc('spend')->values()->all();

        $daily = $rows->groupBy(fn($row) => $row->report_date->toDateString())
            ->map(fn($group, $date) => ['date' => $date] + self::metaTotals($group))
            ->sortKeys()->values()->all();

        return [
            'currency'       => $rows->first()->currency ?? 'INR',
            'days_with_data' => count($daily),
            'totals'         => self::metaTotals($rows),
            'campaigns'      => $campaigns,
            'daily'          => $daily,
        ];
    }

    public static function googleAdsTypeFromServiceName(string $name): ?string
    {
        $normalized = Str::lower($name);

        if (str_contains($normalized, 'youtube') && str_contains($normalized, 'ads')) {
            return 'google_youtube_ads';
        }

        if (str_contains($normalized, 'call') && str_contains($normalized, 'ads')) {
            return 'google_call_ads';
        }

        return str_contains($normalized, 'google') && str_contains($normalized, 'ads')
            ? 'google_ads'
            : null;
    }

    public static function label(string $type): string
    {
        return match ($type) {
            'whatsapp_meta_ads' => 'WhatsApp Meta Ads',
            'lead_generation_ads' => 'Lead Generation Ads',
            'social_media_engagement' => 'Social Media Engagement',
            'google_youtube_ads' => 'YouTube Ads',
            'google_call_ads' => 'Call Ads',
            'google_ads' => 'Google Ads',
            default => Str::headline($type),
        };
    }

    public static function isGoogleAds(string $type): bool
    {
        return in_array($type, ['google_youtube_ads', 'google_call_ads', 'google_ads'], true);
    }

    public static function available(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagepng');
    }

    public static function barGraph(array $metrics): ?string
    {
        $metrics = array_filter($metrics, static fn($v) => $v > 0);
        if (! $metrics || ! self::available()) {
            return null;
        }

        $cnt = count($metrics);
        $w = max(900, $cnt * 120 + 100);
        $h = 380;
        $left = 70;
        $right = 20;
        $top = 40;
        $bottom = 60;
        $plotW = $w - $left - $right;
        $plotH = $h - $top - $bottom;
        $max = (float) max($metrics);

        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, $w, $h, self::alloc($im, '#ffffff'));

        self::drawGrid($im, $left, $top, $plotW, $plotH, $max);

        $slot = $plotW / $cnt;
        $bw = min(70, $slot * 0.6);
        $dark = self::alloc($im, '#111827');
        $gray = self::alloc($im, '#4b5563');

        $i = 0;
        foreach ($metrics as $label => $value) {
            $barH = $value / $max * $plotH;
            $x1 = (int) round($left + $i * $slot + ($slot - $bw) / 2);
            $x2 = (int) round($x1 + $bw);
            $y1 = (int) round($top + $plotH - $barH);
            $y2 = $top + $plotH;

            $color = self::alloc($im, self::PALETTE[$i % count(self::PALETTE)]);
            imagefilledrectangle($im, $x1, $y1, $x2, $y2, $color);

            $cx = ($x1 + $x2) / 2;
            self::centeredText($im, 4, $cx, $y1 - 18, number_format($value), $dark);
            self::centeredText($im, 3, $cx, $y2 + 10, (string) $label, $gray);
            $i++;
        }

        return self::toDataUri($im);
    }

    public static function pie(array $slices): ?string
    {
        $slices = array_filter($slices, static fn($v) => $v > 0);
        $total = array_sum($slices);
        if (! $slices || $total <= 0 || ! self::available()) {
            return null;
        }

        $size = 400;
        $c = $size / 2;
        $d = $size - 20;

        $im = imagecreatetruecolor($size, $size);
        imagefilledrectangle($im, 0, 0, $size, $size, self::alloc($im, '#ffffff'));

        if (count($slices) === 1) {
            imagefilledellipse($im, (int) $c, (int) $c, $d, $d, self::alloc($im, self::PALETTE[0]));
            return self::toDataUri($im);
        }

        $cum = 0;
        $i = 0;
        foreach ($slices as $value) {
            $startDeg = (int) round(270 + $cum / $total * 360);
            $cum += $value;
            $endDeg = (int) round(270 + $cum / $total * 360);

            if ($endDeg > $startDeg) {
                imagefilledarc(
                    $im,
                    (int) $c,
                    (int) $c,
                    $d,
                    $d,
                    $startDeg % 360,
                    $endDeg % 360,
                    self::alloc($im, self::PALETTE[$i % count(self::PALETTE)]),
                    IMG_ARC_PIE
                );
            }
            $i++;
        }

        return self::toDataUri($im);
    }

    public static function lineChart(array $labels, array $values): ?string
    {
        $n = count($values);
        if ($n < 2 || ! self::available()) {
            return null;
        }

        $w = 1000;
        $h = 380;
        $left = 70;
        $right = 30;
        $top = 40;
        $bottom = 60;
        $plotW = $w - $left - $right;
        $plotH = $h - $top - $bottom;
        $max = (float) (max($values) ?: 1);

        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, $w, $h, self::alloc($im, '#ffffff'));

        self::drawGrid($im, $left, $top, $plotW, $plotH, $max);

        $green = self::alloc($im, '#2f855a');
        $dark  = self::alloc($im, '#111827');
        $gray  = self::alloc($im, '#4b5563');

        $pts = [];
        foreach (array_values($values) as $i => $v) {
            $pts[] = [
                (int) round($left + $i * $plotW / ($n - 1)),
                (int) round($top + $plotH - $v / $max * $plotH),
                $v,
                $labels[$i] ?? '',
            ];
        }

        imagesetthickness($im, 3);
        for ($i = 1; $i < $n; $i++) {
            imageline($im, $pts[$i - 1][0], $pts[$i - 1][1], $pts[$i][0], $pts[$i][1], $green);
        }
        imagesetthickness($im, 1);

        $step = max(1, (int) ceil($n / max(1, floor($plotW / 80))));
        foreach ($pts as $i => [$x, $y, $v, $label]) {
            imagefilledellipse($im, $x, $y, 10, 10, $green);
            if ($i % $step === 0) {
                self::centeredText($im, 3, $x, $y - 20, number_format($v), $dark);
                self::centeredText($im, 3, $x, $top + $plotH + 10, (string) $label, $gray);
            }
        }

        return self::toDataUri($im);
    }

    /**
     * Calculate click percentages for Google Ads demographics.
     */
    public static function addClickPercentages(array $data): array
    {
        return self::clickPercentageCallback(
            $data,
            function (array $data, int $totalClicks): array {
                $data['demographics'] = collect($data['demographics'] ?? [])
                    ->map(function (array $row) use ($totalClicks): array {
                        $clicks = (int) ($row['clicks'] ?? 0);

                        $row['click_percentage'] = $totalClicks > 0
                            ? round($clicks / $totalClicks * 100, 1)
                            : 0;

                        return $row;
                    })
                    ->values()
                    ->all();

                return $data;
            }
        );
    }

    /**
     * Private callback for calculating click percentages.
     */
    private static function clickPercentageCallback(
        array $data,
        callable $callback
    ): array {
        $totalClicks = (int) ($data['clicks'] ?? 0);

        return $callback($data, $totalClicks);
    }

    /**
     * Get Google Ads service types from an assignment.
     */
    public static function googleAdsServices(AssignService $assignment): Collection
    {
        return self::googleAdsServicesCallback(
            $assignment,
            function (AssignService $assignment): Collection {
                return $assignment->getServices()
                    ->map(fn($service) => self::googleAdsTypeFromServiceName($service->name))
                    ->filter()
                    ->unique()
                    ->values();
            }
        );
    }

    /**
     * Private callback for Google Ads service detection.
     */
    private static function googleAdsServicesCallback(
        AssignService $assignment,
        callable $callback
    ): Collection {
        return $callback($assignment);
    }

    /**
     * Ensure assignment supports Google Ads.
     */
    public static function ensureGoogleAdsAssignment(AssignService $assignment): void
    {
        self::ensureGoogleAdsAssignmentCallback(
            $assignment,
            function (Collection $services): void {
                abort_if($services->isEmpty(), 404);
            }
        );
    }

    /**
     * Private callback for Google Ads assignment validation.
     */
    private static function ensureGoogleAdsAssignmentCallback(
        AssignService $assignment,
        callable $callback
    ): void {
        $services = self::googleAdsServices($assignment);

        $callback($services);
    }

    /*
|--------------------------------------------------------------------------
| Meta Catalog — primary campaign result
|--------------------------------------------------------------------------
*/

    public static function primaryResult(array $campaign): ?array
    {
        return self::primaryResultCallback(
            $campaign,
            function (array $campaign): ?array {
                $map = [
                    'lead' => 'Leads',
                    'offsite_conversion.fb_pixel_lead' => 'Leads',
                    'onsite_conversion.lead_grouped' => 'Leads',
                    'offsite_complete_registration_add_meta_leads' => 'Registrations',
                    'offsite_search_add_meta_leads' => 'Searches',
                    'offsite_content_view_add_meta_leads' => 'Content views',
                    'purchase' => 'Purchases',
                    'offsite_conversion.fb_pixel_purchase' => 'Purchases',
                    'add_to_cart' => 'Add to cart',
                    'initiate_checkout' => 'Checkouts',
                    'onsite_conversion.messaging_conversation_started_7d' => 'Messaging conversations',
                    'link_click' => 'Link clicks',
                    'post_engagement' => 'Post engagement',
                    'video_view' => 'Video views',
                ];

                $actions = collect($campaign['actions'] ?? [])
                    ->filter(fn($action) => isset($action['action_type'], $action['value']))
                    ->keyBy('action_type');

                foreach ($map as $type => $label) {
                    if ($actions->has($type)) {
                        return [
                            'label' => $label,
                            'value' => (float) $actions[$type]['value'],
                        ];
                    }
                }

                return null;
            }
        );
    }

    private static function primaryResultCallback(
        array $campaign,
        callable $callback
    ): ?array {
        return $callback($campaign);
    }


    /*
|--------------------------------------------------------------------------
| Social Media Reports — GST percentage
|--------------------------------------------------------------------------
*/

    public static function gstPercentage(): ?float
    {
        return self::gstPercentageCallback(
            function (): ?float {
                $percentage = GstMaster::query()
                    ->where('status', true)
                    ->latest('id')
                    ->value('percentage');

                return $percentage === null ? null : (float) $percentage;
            }
        );
    }

    private static function gstPercentageCallback(callable $callback): ?float
    {
        return $callback();
    }


    /*
|--------------------------------------------------------------------------
| Social Media Reports — parse amount
|--------------------------------------------------------------------------
*/

    public static function parseAmount(mixed $value): float
    {
        return self::parseAmountCallback(
            $value,
            fn($value): float => (float) preg_replace(
                '/[^0-9.]/',
                '',
                (string) $value
            )
        );
    }

    private static function parseAmountCallback(
        mixed $value,
        callable $callback
    ): float {
        return $callback($value);
    }


    /*
|--------------------------------------------------------------------------
| Social Media Reports — spend summary
|--------------------------------------------------------------------------
*/

    public static function spendSummary(SocialMediaReport $report): array
    {
        return self::spendSummaryCallback(
            $report,
            function (SocialMediaReport $report): array {
                $budget = (float) ($report->assignService?->budget ?? 0);

                $spent = collect($report->campaign_results ?? [])
                    ->sum(fn($row) => self::parseAmount($row['spend'] ?? 0));

                $gstPercentage = self::gstPercentage();

                $gstAmount = $gstPercentage === null
                    ? 0
                    : round($spent * $gstPercentage / 100, 2);

                $spentIncludingGst = $spent + $gstAmount;

                return [
                    'budget'              => $budget,
                    'spent'               => $spent,
                    'gst_percentage'      => $gstPercentage,
                    'gst_amount'          => $gstAmount,
                    'spent_including_gst' => $spentIncludingGst,
                    'available'           => $budget - $spentIncludingGst,
                ];
            }
        );
    }

    private static function spendSummaryCallback(
        SocialMediaReport $report,
        callable $callback
    ): array {
        return $callback($report);
    }


    /*
|--------------------------------------------------------------------------
| Social Media Reports — multiple spend summaries
|--------------------------------------------------------------------------
*/

    public static function spendSummariesFor($reports): array
    {
        return self::spendSummariesCallback(
            $reports,
            function ($reports): array {
                $summaries = [];

                foreach ($reports as $report) {
                    $report->loadMissing('assignService');

                    $summaries[$report->id] = self::spendSummary($report);
                }

                return $summaries;
            }
        );
    }

    private static function spendSummariesCallback(
        $reports,
        callable $callback
    ): array {
        return $callback($reports);
    }


    /*
|--------------------------------------------------------------------------
| Social Media Reports — report types
|--------------------------------------------------------------------------
*/

    public static function reportTypesFor($reports, array $assignServices): array
    {
        return self::reportTypesCallback(
            $reports,
            $assignServices,
            function ($reports, array $assignServices): array {
                $nameToType = [
                    'WhatsApp Meta Ads Monthly Report' => 'whatsapp_meta_ads',
                    'Lead Generation Ads Monthly Report' => 'lead_generation_ads',
                    'Social Media Engagement Monthly Report' => 'social_media_engagement',
                ];

                $serviceNames = Service::pluck('name', 'id');
                $result = [];

                foreach ($reports as $report) {
                    $assignment = $assignServices[(int) $report->assign_service_id] ?? null;
                    $serviceIds = $assignment?->service_id;

                    $ids = is_array($serviceIds)
                        ? $serviceIds
                        : array_filter([(int) $serviceIds]);

                    $types = collect($ids)
                        ->map(fn($serviceId) => $nameToType[$serviceNames[$serviceId] ?? ''] ?? null)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $result[$report->id] = $types;
                }

                return $result;
            }
        );
    }

    private static function reportTypesCallback(
        $reports,
        array $assignServices,
        callable $callback
    ): array {
        return $callback($reports, $assignServices);
    }


    /*
|--------------------------------------------------------------------------
| Social Media Reports — load assigned services for PDF
|--------------------------------------------------------------------------
*/

    public static function assignServicesFor($reports): array
    {
        return self::assignServicesCallback(
            $reports,
            function ($reports): array {
                $ids = collect($reports)
                    ->pluck('assign_service_id')
                    ->filter(fn($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($ids->isEmpty()) {
                    return [];
                }

                return AssignService::with('client')
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id')
                    ->all();
            }
        );
    }

    private static function assignServicesCallback(
        $reports,
        callable $callback
    ): array {
        return $callback($reports);
    }

    private static function metaTotals(Collection $rows): array
    {
        $spend = round((float) $rows->sum('spend'), 2);
        $impressions = (int) $rows->sum('impressions');
        $clicks = (int) $rows->sum('clicks');
        $leads = (int) $rows->sum('leads');
        $conversations = (int) $rows->sum('messaging_conversations');

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

    private static function guessMetaType(array $totals): string
    {
        if ($totals['leads'] > 0) {
            return 'lead generation';
        }

        if ($totals['messaging_conversations'] > 0) {
            return 'messaging / WhatsApp';
        }

        return 'awareness / engagement';
    }

    private static function alloc($im, string $hex)
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        return imagecolorallocate($im, $r, $g, $b);
    }

    private static function centeredText($im, int $font, float $cx, int $y, string $text, $color): void
    {
        $w = imagefontwidth($font) * strlen($text);
        imagestring($im, $font, (int) round($cx - $w / 2), $y, $text, $color);
    }

    private static function rightText($im, int $font, int $xRight, int $y, string $text, $color): void
    {
        $w = imagefontwidth($font) * strlen($text);
        imagestring($im, $font, $xRight - $w, $y, $text, $color);
    }

    private static function toDataUri($im): string
    {
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    private static function drawGrid($im, int $left, int $top, int $plotW, int $plotH, float $max): void
    {
        $grid = self::alloc($im, '#e5e7eb');
        $axis = self::alloc($im, '#9ca3af');
        $txt  = self::alloc($im, '#6b7280');

        for ($i = 0; $i <= 4; $i++) {
            $y = (int) round($top + $plotH - ($plotH * $i / 4));
            imageline($im, $left, $y, $left + $plotW, $y, $i === 0 ? $axis : $grid);
            self::rightText($im, 3, $left - 8, $y - 6, number_format($max * $i / 4), $txt);
        }
        imageline($im, $left, $top, $left, $top + $plotH, $axis);
    }

    public static function campaignOptions(): Collection
    {
        return MetaAdsDailyStat::query()
            ->whereDate('report_date', now()->toDateString())
            ->selectRaw('campaign_id, MAX(campaign_name) as campaign_name,
             SUM(leads) as total_leads,
             SUM(messaging_conversations) as total_msgs,
             SUM(spend) as total_spend')
            ->groupBy('campaign_id')
            ->orderBy('campaign_name')
            ->get()
            ->map(fn($c) => self::decorateCampaign($c));
    }

    private static function decorateCampaign(object $c): object
    {
        $c->types = self::campaignTypes($c);

        return $c;
    }

    private static function campaignTypes(object $c): array
    {
        return self::campaignTypesFor(
            (string) ($c->campaign_name ?? ''),
            (float) ($c->total_leads ?? 0) > 0,
            (float) ($c->total_msgs ?? 0) > 0
        );
    }

    public static function campaignTypesFromApi(array $campaign): array
    {
        $actions = collect($campaign['actions'] ?? [])
            ->filter(fn($action) => is_array($action)
                && isset($action['action_type'], $action['value'])
                && is_numeric($action['value'])
                && (float) $action['value'] > 0)
            ->keyBy('action_type');

        return self::campaignTypesFor(
            (string) ($campaign['campaign_name'] ?? ''),
            $actions->has('lead') || $actions->has('onsite_conversion.lead_grouped'),
            $actions->has('onsite_conversion.messaging_conversation_started_7d')
        );
    }

    private static function campaignTypesFor(string $campaignName, bool $hasLeads, bool $hasMessages): array
    {
        $name  = strtolower($campaignName);
        $types = [];

        if (preg_match('/whats\s?app|\bwa\b/', $name)) {
            $types[] = 'whatsapp_meta_ads';
        }
        if (str_contains($name, 'lead')) {
            $types[] = 'lead_generation_ads';
        }
        if (str_contains($name, 'engagement')) {
            $types[] = 'social_media_engagement';
        }

        // Name gave no hint: fall back to what the campaign actually produced
        if (! $types) {
            if ($hasLeads) {
                return ['lead_generation_ads'];
            }
            if ($hasMessages) {
                return ['whatsapp_meta_ads'];
            }
        }

        return $types;
    }
}
