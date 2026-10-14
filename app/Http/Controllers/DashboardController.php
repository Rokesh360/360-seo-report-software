<?php

namespace App\Http\Controllers;

use App\Models\AssignService;
use App\Models\Client;
use App\Models\SocialMediaReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month    = $this->resolveMonth($request->query('month'));
        $start    = $month->copy()->startOfMonth();
        $end      = $month->copy()->endOfMonth();
        $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();
        $clientId  = $request->integer('client_id') ?: null;

        $assignServices = AssignService::with('client:id,name')->get();
        $assignById     = $assignServices->keyBy('id');

        // null = all clients, array = only these assign services
        $scopedIds = $clientId
            ? $assignServices->where('client_id', $clientId)->pluck('id')->map(fn ($i) => (int) $i)->all()
            : null;

        $current  = $this->reportsBetween($start, $end, $scopedIds);
        $previous = $this->reportsBetween($prevStart, $prevEnd, $scopedIds);

        $days   = $start->daysInMonth;
        $daily  = $this->dailySeries($current, $days);
        $nowT   = $this->totals($current);
        $prevT  = $this->totals($previous);

        /* ---------------- Metric cards ---------------- */
        $cards = [];
        foreach ([
            'reach'       => ['Reach',              'green', 0],
            'impressions' => ['Impressions',        'blue',  1],
            'clicks'      => ['Clicks',             'amber', 2],
            'leads'       => ['Leads & enquiries',  'coral', 3],
        ] as $key => [$label, $color, $icon]) {
            $cards[] = [
                'label'  => $label,
                'color'  => $color,
                'icon'   => $icon,
                'value'  => $this->shortNumber($nowT[$key]),
                'change' => $prevT[$key] > 0
                    ? round(($nowT[$key] - $prevT[$key]) / $prevT[$key] * 100, 1)
                    : null,
                'spark'  => $this->sparkHeights($daily[$key]),
            ];
        }

        /* ---------------- Line chart ---------------- */
        $chart = $this->buildChart($daily['clicks'], $daily['impressions'], $start);

        /* ---------------- Engagement donut ---------------- */
        $donut = $this->buildDonut([
            'Reactions' => [(int) $current->sum('reactions'), '#4b9a70'],
            'Comments'  => [(int) $current->sum('comments'),  '#4a7fc1'],
            'Shares'    => [(int) $current->sum('shares'),    '#d99a2b'],
            'Saves'     => [(int) $current->sum('saves'),     '#e0715a'],
        ]);

        /* ---------------- Client performance table ---------------- */
        $clientRows = $this->clientRows($assignServices, $assignById, $current, $start, $end, $clientId);

        return view('admin.dashboard', [
            'month'        => $month,
            'start'        => $start,
            'end'          => $end,
            'periodLabel'  => $month->format('F Y'),
            'greeting'     => now()->hour < 12 ? 'Good Morning' : (now()->hour < 17 ? 'Good Afternoon' : 'Good Evening'),
            'cards'        => $cards,
            'chart'        => $chart,
            'donut'        => $donut,
            'clientRows'   => $clientRows,
            'reportCount'  => $current->count(),
            'monthOptions' => collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonthsNoOverflow($i)),
            'clients'      => Client::orderBy('name')->get(['id', 'name']),
            'clientId'     => $clientId,
        ]);
    }

    /* ============================================================ */

    private function resolveMonth(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('!Y-m', $value) : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    private function reportsBetween(Carbon $from, Carbon $to, ?array $scopedIds): Collection
    {
        return SocialMediaReport::query()
            ->whereBetween('created_at', [$from->toDateString(), $to->toDateString()])
            ->when($scopedIds !== null, fn ($query) => $query->whereIn('assign_service_id', $scopedIds))
            ->get([
                'id', 'assign_service_id', 'created_at',
                'reach', 'impressions', 'whatsapp_clicks', 'link_clicks',
                'total_leads', 'total_enquiries',
                'reactions', 'comments', 'shares', 'saves',
            ]);
    }

    private function totals(Collection $r): array
    {
        return [
            'reach'       => (int) $r->sum('reach'),
            'impressions' => (int) $r->sum('impressions'),
            'clicks'      => (int) $r->sum('whatsapp_clicks') + (int) $r->sum('link_clicks'),
            'leads'       => (int) $r->sum('total_leads') + (int) $r->sum('total_enquiries'),
        ];
    }

    private function dailySeries(Collection $reports, int $days): array
    {
        $s = [
            'reach'       => array_fill(1, $days, 0),
            'impressions' => array_fill(1, $days, 0),
            'clicks'      => array_fill(1, $days, 0),
            'leads'       => array_fill(1, $days, 0),
        ];

        foreach ($reports as $r) {
            $d = $r->created_at->day;
            $s['reach'][$d]       += (int) $r->reach;
            $s['impressions'][$d] += (int) $r->impressions;
            $s['clicks'][$d]      += (int) $r->whatsapp_clicks + (int) $r->link_clicks;
            $s['leads'][$d]       += (int) $r->total_leads + (int) $r->total_enquiries;
        }

        return $s;
    }

    private function sparkHeights(array $daily, int $bars = 10): array
    {
        $values = array_values($daily);
        $n = count($values);
        $buckets = [];

        for ($i = 0; $i < $bars; $i++) {
            $from = (int) floor($i * $n / $bars);
            $to   = (int) floor(($i + 1) * $n / $bars);
            $buckets[] = array_sum(array_slice($values, $from, max(1, $to - $from)));
        }

        $max = max($buckets);

        return array_map(fn ($v) => $max > 0 ? max(8, (int) round($v / $max * 100)) : 8, $buckets);
    }

    private function buildChart(array $clicks, array $impressions, Carbon $start): array
    {
        $days = count($clicks);
        $w = 760; $top = 18; $bottom = 180; $h = $bottom - $top;

        $impStep = $this->niceStep(max($impressions) / 3);
        $impMax  = $impStep * 3;
        $clkMax  = max(max($clicks), 1);

        $impPts = $clkPts = [];
        foreach (range(1, $days) as $i) {
            $x = ($i - 1) / ($days - 1) * $w;
            $impPts[] = [$x, $bottom - ($impressions[$i] / $impMax) * $h];
            $clkPts[] = [$x, $bottom - ($clicks[$i] / $clkMax) * $h];
        }

        $clicksPath = $this->smoothPath($clkPts);
        $peakDay    = array_search(max($clicks), $clicks, true);

        $xLabels = collect([1, 6, 11, 16, 21, 26, $days])->unique()
            ->map(fn ($d) => $start->copy()->day($d)->format('M d'))->all();

        return [
            'hasData'         => array_sum($clicks) + array_sum($impressions) > 0,
            'impressionsPath' => $this->smoothPath($impPts),
            'clicksPath'      => $clicksPath,
            'areaPath'        => $clicksPath ? $clicksPath . sprintf(' L%.1F %d L0 %d Z', $w, $bottom, $bottom) : '',
            'peak'            => max($clicks) > 0 ? $clkPts[$peakDay - 1] : null,
            'peakClicks'      => $this->shortNumber(max($clicks)),
            'peakImpressions' => $this->shortNumber(max($impressions)),
            'yLabels'         => [
                $this->shortNumber($impStep * 3),
                $this->shortNumber($impStep * 2),
                $this->shortNumber($impStep),
                '0',
            ],
            'xLabels'         => $xLabels,
        ];
    }

    /** Smooth line through points using horizontal-tangent cubic beziers (no overshoot). */
    private function smoothPath(array $pts): string
    {
        if (count($pts) < 2) {
            return '';
        }

        $d = sprintf('M%.1F %.1F', $pts[0][0], $pts[0][1]);
        for ($i = 1; $i < count($pts); $i++) {
            $cx = ($pts[$i - 1][0] + $pts[$i][0]) / 2;
            $d .= sprintf(' C%.1F %.1F %.1F %.1F %.1F %.1F',
                $cx, $pts[$i - 1][1], $cx, $pts[$i][1], $pts[$i][0], $pts[$i][1]);
        }

        return $d;
    }

    private function buildDonut(array $input): array
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
                'label'  => $label,
                'value'  => $value,
                'color'  => $color,
                'pct'    => round($value / $total * 100, 1),
                'dash'   => sprintf('%.2F %.2F', $len, $circ - $len),
                'offset' => sprintf('%.2F', -$offset),
            ];
            $offset += $len;
        }

        return ['r' => $r, 'total' => $total, 'totalLabel' => $this->shortNumber($total), 'slices' => $slices];
    }

    private function clientRows(Collection $assignServices, Collection $assignById, Collection $reports, Carbon $start, Carbon $end, ?int $clientId): Collection
    {
        $rows = [];

        // Clients with an assignment overlapping this month (so "no entries" clients still show)
        foreach ($assignServices as $a) {
            if (! $a->client_id || ($clientId && $a->client_id !== $clientId)) {
                continue;
            }
            if ($a->from_date && $a->to_date && $a->from_date->lte($end) && $a->to_date->gte($start)) {
                $rows[$a->client_id] ??= $this->blankRow($a->client?->name);
                $rows[$a->client_id]['services']++;
            }
        }

        foreach ($reports as $r) {
            $clientId = $assignById[(int) $r->assign_service_id]->client_id ?? null;
            $cids = collect([$clientId])->filter();

            foreach ($cids as $cid) {
                $rows[$cid] ??= $this->blankRow($assignById->firstWhere('client_id', $cid)?->client?->name);
                $rows[$cid]['reports']++;
                $rows[$cid]['reach']  += (int) $r->reach;
                $rows[$cid]['clicks'] += (int) $r->whatsapp_clicks + (int) $r->link_clicks;
                $rows[$cid]['last']    = max($rows[$cid]['last'], $r->created_at->toDateString());
            }
        }

        $max = max(array_merge([0], array_column($rows, 'reach')));

        return collect($rows)
            ->map(function ($row) use ($max) {
                $row['share']      = $max > 0 ? (int) round($row['reach'] / $max * 100) : 0;
                $row['reachLabel'] = $this->shortNumber($row['reach']);
                $row['lastLabel']  = $row['last'] ? Carbon::parse($row['last'])->format('d M') : 'No entries';
                return $row;
            })
            ->sortByDesc('reach')->take(8)->values();
    }

    private function blankRow(?string $name): array
    {
        return ['name' => $name ?: 'Unknown client', 'services' => 0, 'reports' => 0, 'reach' => 0, 'clicks' => 0, 'last' => ''];
    }

    private function niceStep(float $v): int
    {
        if ($v <= 0) {
            return 1;
        }
        $p = 10 ** floor(log10($v));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($v <= $m * $p) {
                return (int) max(1, ceil($m * $p));
            }
        }
        return (int) ceil(10 * $p);
    }

    private function shortNumber(int|float $n): string
    {
        $n = (float) $n;
        if ($n >= 1_000_000) return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
        if ($n >= 1_000)     return rtrim(rtrim(number_format($n / 1_000, 1), '0'), '.') . 'K';
        return number_format($n);
    }
}