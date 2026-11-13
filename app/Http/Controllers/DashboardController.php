<?php

namespace App\Http\Controllers;

use App\Support\Helpers;
use App\Models\AssignService;
use App\Models\Client;
use App\Models\GstMaster;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month     = Helpers::resolveMonth($request->query('month'));
        $start     = $month->copy()->startOfMonth();
        $end       = $month->copy()->endOfMonth();
        $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();

        // No "All clients" option: fall back to the first client when none/unknown is chosen.
        $clients  = Client::orderBy('name')->get(['id', 'name']);
        $clientId = $request->integer('client_id') ?: null;

        if (! $clientId || ! $clients->contains('id', $clientId)) {
            $clientId = $clients->first()?->id;
        }

        $assignServices = AssignService::with('client:id,name')->get();
        $assignById     = $assignServices->keyBy('id');

        // null = all clients, array = only these assign services
        $scopedIds = $clientId
            ? $assignServices->where('client_id', $clientId)->pluck('id')->map(fn($i) => (int) $i)->all()
            : null;

        $current  = Helpers::reportsBetween($start, $end, $scopedIds);
        $previous = Helpers::reportsBetween($prevStart, $prevEnd, $scopedIds);

        $days  = $start->daysInMonth;
        $daily = Helpers::dailySeries($current, $days);
        $nowT  = Helpers::totals($current);
        $prevT = Helpers::totals($previous);

        /* ---------------- Metric cards ---------------- */
        $cards = [];
        foreach (
            [
                'reach'       => ['Reach',             'green', 0],
                'impressions' => ['Impressions',       'blue',  1],
                'clicks'      => ['Clicks',            'amber', 2],
                'leads'       => ['Leads & enquiries', 'coral', 3],
            ] as $key => [$label, $color, $icon]
        ) {
            $cards[] = [
                'label'  => $label,
                'color'  => $color,
                'icon'   => $icon,
                'value'  => Helpers::shortNumber($nowT[$key]),
                'change' => $prevT[$key] > 0
                    ? round(($nowT[$key] - $prevT[$key]) / $prevT[$key] * 100, 1)
                    : null,
                'spark'  => Helpers::sparkHeights($daily[$key]),
            ];
        }

        /* ---------------- Line chart ---------------- */
        $chart = Helpers::buildChart($daily['clicks'], $daily['impressions'], $start);

        /* ---------------- Engagement donut ---------------- */
        $donut = Helpers::buildDonut([
            'Reactions' => [(int) $current->sum('reactions'), '#4b9a70'],
            'Comments'  => [(int) $current->sum('comments'),  '#4a7fc1'],
            'Shares'    => [(int) $current->sum('shares'),    '#d99a2b'],
            'Saves'     => [(int) $current->sum('saves'),     '#e0715a'],
        ]);

        /* ---------------- Tables ---------------- */
        $clientRows    = Helpers::clientRows($assignServices, $assignById, $current, $start, $end, $clientId);
        $gstPercentage = GstMaster::query()
            ->where('status', true)
            ->latest('id')
            ->value('percentage');
        $gstRate = $gstPercentage !== null ? (float) $gstPercentage / 100 : 0;

        // Spend is counted only inside each assignment's from_date..to_date,
        // GST is applied inside the helper, and rows are already filtered/sorted.
        $lowBudgetRows = Helpers::lowBudgetRows($assignServices, $clientId, $gstRate);

        // Independent of the low-budget list: an assignment can appear in both.
        $deadlineRows = Helpers::deadlineRows($assignServices, $clientId);

        return view('admin.dashboard', [
            'month'          => $month,
            'start'          => $start,
            'end'            => $end,
            'periodLabel'    => $month->format('F Y'),
            'greeting'       => now()->hour < 12 ? 'Good Morning' : (now()->hour < 17 ? 'Good Afternoon' : 'Good Evening'),
            'cards'          => $cards,
            'chart'          => $chart,
            'donut'          => $donut,
            'clientRows'     => $clientRows,
            'reportCount'    => $current->count(),
            'monthOptions'   => collect(range(0, 11))->map(fn($i) => now()->startOfMonth()->subMonthsNoOverflow($i)),
            'clients'        => $clients,
            'clientId'       => $clientId,
            'lowBudgetRows'  => $lowBudgetRows,
            'lowBudgetLimit' => Helpers::LOW_BUDGET_LIMIT,
            'deadlineRows'   => $deadlineRows,
            'deadlineDays'   => Helpers::DEADLINE_ALERT_DAYS,
            'gstPercentage'  => $gstPercentage,
        ]);
    }
}