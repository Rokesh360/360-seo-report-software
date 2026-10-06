<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SeoReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // -------- Period selection (defaults to latest report month) --------
        $latestReport = SeoReport::latest('reporting_period')->first();

        $period = $request->input('period'); // expected "2026-09"
        if (! $period || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = $latestReport?->reporting_period
                ? Carbon::parse($latestReport->reporting_period . '-01')->format('Y-m')
                : now()->format('Y-m');
        }

        $periodDate  = Carbon::createFromFormat('Y-m', $period);
        $prevDate    = $periodDate->copy()->subMonth();

        // -------- Reports for this period and previous --------
        $reports = SeoReport::with('client')
            ->where('reporting_period', $periodDate->format('Y-m'))
            ->get();

        $prevReports = SeoReport::with('client')
            ->where('reporting_period', $prevDate->format('Y-m'))
            ->get();

        // -------- Aggregate metrics --------
        $clicks       = $this->sumMetric($reports, 'organic_clicks');
        $impressions  = $this->sumMetric($reports, 'search_impressions');
        $avgPosition  = $this->avgMetric($reports, 'average_position');
        $keywords     = $this->sumMetric($reports, 'tracked_keywords');

        $prevClicks      = $this->sumMetric($prevReports, 'organic_clicks');
        $prevImpressions = $this->sumMetric($prevReports, 'search_impressions');
        $prevPosition    = $this->avgMetric($prevReports, 'average_position');
        $prevKeywords    = $this->sumMetric($prevReports, 'tracked_keywords');

        // -------- Keyword table (flattened across reports) --------
        $keywordRows = collect();
        foreach ($reports as $report) {
            foreach (($report->keywords ?? []) as $k) {
                $keywordRows->push([
                    'keyword'   => $k['keyword'] ?? '',
                    'url'       => $report->client?->website ?? '',
                    'position'  => $k['position'] ?? null,
                    'change'    => $k['change'] ?? null,
                    'volume'    => $k['volume'] ?? null,
                    'visibility'=> $k['visibility'] ?? null,
                ]);
            }
        }
        $keywordRows = $keywordRows->filter(fn ($r) => $r['keyword'] !== '')
            ->sortBy('position')
            ->take(10)
            ->values();

        // -------- Chart data (daily clicks / impressions) --------
        $chart = $this->buildChartSeries($reports, $periodDate);

        // -------- Site health (derive from reports or fallback) --------
        $healthScore = (int) round($reports->avg('site_health_score') ?? 92);
        $health = [
            'score'    => $healthScore,
            'passed'   => (int) round($healthScore * 0.42),
            'attention'=> max(0, 43 - (int) round($healthScore * 0.42) - 1),
            'critical' => max(0, 43 - (int) round($healthScore * 0.42) - max(0, 43 - (int) round($healthScore * 0.42) - 1)),
        ];

        return view('admin.dashboard', [
            'periodLabel'  => $periodDate->format('F Y'),
            'periodRange'  => $periodDate->copy()->startOfMonth()->format('d M')
                . ' — ' . $periodDate->copy()->endOfMonth()->format('d M Y'),
            'periodValue'  => $period,
            'clients'      => Client::orderBy('name')->get(),
            'metrics'      => [
                'clicks'      => $clicks,
                'impressions' => $impressions,
                'position'    => $avgPosition,
                'keywords'    => $keywords,
            ],
            'deltas'       => [
                'clicks'      => $this->pctChange($clicks, $prevClicks),
                'impressions' => $this->pctChange($impressions, $prevImpressions),
                'position'    => $avgPosition && $prevPosition ? round($prevPosition - $avgPosition, 1) : 0,
                'keywords'    => $keywords - $prevKeywords,
            ],
            'keywordRows'  => $keywordRows,
            'chart'        => $chart,
            'health'       => $health,
        ]);
    }

    // ---------------- Helpers ----------------

    private function sumMetric($reports, string $column): float
    {
        return (float) $reports->sum(fn ($r) => (float) ($r->{$column} ?? 0));
    }

    private function avgMetric($reports, string $column): ?float
    {
        $values = $reports->pluck($column)->filter(fn ($v) => $v !== null && $v !== '');
        return $values->isEmpty() ? null : round($values->avg(), 1);
    }

    private function pctChange($current, $previous): ?float
    {
        if (! $previous || $previous == 0) {
            return $current > 0 ? 100.0 : null;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Build a smooth SVG path from stored daily points (if present),
     * otherwise generate a gentle upward trend seeded by totals.
     */
    private function buildChartSeries($reports, Carbon $periodDate): array
    {
        $days = $periodDate->daysInMonth;

        // Try to use a "chart_data" JSON column if you add one, e.g.:
        // ['clicks' => [..30..], 'impressions' => [..30..]]
        $clicksDaily = [];
        $impressionsDaily = [];

        foreach ($reports as $report) {
            $data = $report->chart_data ?? null;
            if (is_array($data)) {
                foreach (($data['clicks'] ?? []) as $i => $v) {
                    $clicksDaily[$i] = ($clicksDaily[$i] ?? 0) + (float) $v;
                }
                foreach (($data['impressions'] ?? []) as $i => $v) {
                    $impressionsDaily[$i] = ($impressionsDaily[$i] ?? 0) + (float) $v;
                }
            }
        }

        // Fallback: synthesize a smooth rising curve from totals
        if (empty($clicksDaily)) {
            $totalClicks = $this->sumMetric($reports, 'organic_clicks') ?: 24860;
            $totalImpr   = $this->sumMetric($reports, 'search_impressions') ?: 486200;

            for ($i = 0; $i < $days; $i++) {
                $t = $i / max(1, $days - 1);                 // 0 → 1
                $wave = 1 + 0.06 * sin($i * 1.7);            // subtle wiggle
                $clicksDaily[$i]      = ($totalClicks / $days) * (0.72 + 0.55 * $t) * $wave;
                $impressionsDaily[$i] = ($totalImpr   / $days) * (0.70 + 0.60 * $t) * $wave;
            }
        }

        ksort($clicksDaily);
        ksort($impressionsDaily);

        $maxClicks = max($clicksDaily ?: [1]);
        $maxImpr   = max($impressionsDaily ?: [1]);

        return [
            'clicksPath'      => $this->svgPath(array_values($clicksDaily), $maxClicks),
            'clicksAreaPath'  => $this->svgAreaPath(array_values($clicksDaily), $maxClicks),
            'impressionsPath' => $this->svgPath(array_values($impressionsDaily), $maxImpr),
            'yMaxClicks'      => $this->humanNumber($maxClicks),
            'yMidClicks'      => $this->humanNumber($maxClicks * 0.66),
            'yLowClicks'      => $this->humanNumber($maxClicks * 0.33),
            'xLabels'         => $this->xLabels($periodDate),
            'pointIndex'      => (int) floor(count($clicksDaily) * 0.78),
        ];
    }

    /** Convert a numeric series into an SVG cubic path across viewBox 0..760 x 0..180. */
    private function svgPath(array $values, float $max): string
    {
        $n = count($values);
        if ($n === 0) return '';
        $w = 760; $h = 180; $top = 18;

        $pts = [];
        foreach ($values as $i => $v) {
            $x = $n === 1 ? 0 : ($i / ($n - 1)) * $w;
            $y = $top + (1 - ($v / max($max, 0.0001))) * ($h - $top);
            $pts[] = [$x, $y];
        }

        $d = "M{$pts[0][0]} {$pts[0][1]}";
        for ($i = 1; $i < $n; $i++) {
            $prev = $pts[$i - 1];
            $cur  = $pts[$i];
            $cx1  = $prev[0] + ($cur[0] - $prev[0]) / 2;
            $cx2  = $prev[0] + ($cur[0] - $prev[0]) / 2;
            $d   .= " C{$cx1} {$prev[1]} {$cx2} {$cur[1]} {$cur[0]} {$cur[1]}";
        }
        return $d;
    }

    private function svgAreaPath(array $values, float $max): string
    {
        $line = $this->svgPath($values, $max);
        return $line . ' L760 180 L0 180 Z';
    }

    private function humanNumber(float $n): string
    {
        if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
        if ($n >= 1000)    return round($n / 1000, $n >= 10000 ? 0 : 1) . 'K';
        return (string) round($n);
    }

    private function xLabels(Carbon $period): array
    {
        $days = $period->daysInMonth;
        return [
            $period->copy()->startOfMonth()->format('M d'),
            $period->copy()->day(6)->format('M d'),
            $period->copy()->day(11)->format('M d'),
            $period->copy()->day(16)->format('M d'),
            $period->copy()->day(21)->format('M d'),
            $period->copy()->day(26)->format('M d'),
            $period->copy()->endOfMonth()->format('M d'),
        ];
    }
}