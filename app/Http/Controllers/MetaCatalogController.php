<?php

namespace App\Http\Controllers;

use App\Services\MetaCatalogService;
use Illuminate\Http\RedirectResponse;
use App\Models\MetaAdsDailyStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class MetaCatalogController extends Controller
{
    public function index(Request $request, MetaCatalogService $service): View
    {
        $ranges = ['today' => 'Today'] + MetaCatalogService::RANGES;

        $filters = $request->validate([
            'range' => ['nullable', 'in:' . implode(',', array_keys($ranges))],
            'since' => ['nullable', 'required_if:range,custom', 'date', 'before_or_equal:today'],
            'until' => ['nullable', 'required_if:range,custom', 'date', 'after_or_equal:since', 'before_or_equal:today'],
        ]);

        $filters['range'] ??= 'today';

        $serviceFilters = $filters;
        if ($filters['range'] === 'today') {
            $serviceFilters = [
                'range' => 'custom',
                'since' => now()->toDateString(),
                'until' => now()->toDateString(),
            ];
        }

        try {
            $data = $service->dashboard($serviceFilters);
            $error = null;
        } catch (InvalidArgumentException $e) {
            $data = null;
            $error = $e->getMessage();
        }

        return view('meta_catalog.index', [
            'filters'      => $filters,
            'ranges'       => $ranges,
            'data'         => $data,
            'error'        => $error,
            'alreadySaved' => MetaAdsDailyStat::whereDate('report_date', now()->toDateString())->exists(),
        ]);
    }

    public function connect(Request $request, MetaCatalogService $service): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('meta_oauth_state', $state);

        return redirect()->away($service->loginUrl($state));
    }

    public function callback(Request $request, MetaCatalogService $service): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('meta-catalog.index')
                ->with('error', 'Facebook login was cancelled or denied.');
        }

        $expected = $request->session()->pull('meta_oauth_state');

        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('meta-catalog.index')
                ->with('error', 'Invalid login state. Please try again.');
        }

        try {
            $service->exchangeCode((string) $request->query('code'));
        } catch (RuntimeException $e) {
            return redirect()->route('meta-catalog.index')->with('error', $e->getMessage());
        }

        return redirect()->route('meta-catalog.index');
    }

    public function storeToday(MetaCatalogService $service): RedirectResponse
    {
        $today = now()->toDateString();

        try {
            // Fetch only today's data using the custom range the service already supports
            $data = $service->dashboard([
                'range' => 'custom',
                'since' => $today,
                'until' => $today,
            ]);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('meta-catalog.index')->with('error', $e->getMessage());
        }

        if (! ($data['campaigns']['ok'] ?? false)) {
            return redirect()->route('meta-catalog.index')
                ->with('error', $data['campaigns']['error'] ?? 'Could not fetch campaign data from Meta.');
        }

        $campaignRows = $data['campaigns']['data']['data'] ?? $data['campaigns']['data'] ?? [];
        $campaigns = collect($campaignRows);

        if ($campaigns->isEmpty()) {
            return redirect()->route('meta-catalog.index')
                ->with('error', "No campaign data found for {$today}.");
        }

        $hasInvalidCampaign = $campaigns->contains(
            fn ($campaign) => ! is_array($campaign)
                || empty($campaign['campaign_id'])
                || ! isset($campaign['spend'])
                || ! is_numeric($campaign['spend'])
        );

        if ($hasInvalidCampaign) {
            return redirect()->route('meta-catalog.index')
                ->with('error', 'Meta returned incomplete campaign data. No data was saved; please try again.');
        }

        if ($campaigns->pluck('campaign_id')->unique()->count() !== $campaigns->count()) {
            return redirect()->route('meta-catalog.index')
                ->with('error', 'Meta returned duplicate campaign rows. No data was saved; please try again.');
        }

        $action = function (array $c, array $types): int {
            $actions = collect($c['actions'] ?? [])->keyBy('action_type');
            foreach ($types as $type) {
                if ($actions->has($type)) {
                    return (int) $actions[$type]['value'];
                }
            }
            return 0;
        };

        DB::transaction(function () use ($campaigns, $today, $action) {
            MetaAdsDailyStat::whereDate('report_date', $today)->delete();

            foreach ($campaigns as $c) {
                MetaAdsDailyStat::create([
                    'report_date'             => $today,
                    'campaign_id'             => $c['campaign_id'],
                    'campaign_name'           => $c['campaign_name'] ?? null,
                    'spend'                   => $c['spend'],
                    'impressions'             => (int) ($c['impressions'] ?? 0),
                    'clicks'                  => (int) ($c['clicks'] ?? 0),
                    'reach'                   => (int) ($c['reach'] ?? 0),
                    'ctr'                     => $c['ctr'] ?? 0,
                    'cpc'                     => $c['cpc'] ?? 0,
                    'cpm'                     => $c['cpm'] ?? 0,
                    'leads'                   => $action($c, ['lead', 'onsite_conversion.lead_grouped']),
                    'messaging_conversations' => $action($c, ['onsite_conversion.messaging_conversation_started_7d']),
                    'currency'                => $c['account_currency'] ?? null,
                    'actions'                 => $c['actions'] ?? [],
                ]);
            }
        });

        return redirect()->route('meta-catalog.index')
            ->with('success', "Saved {$campaigns->count()} campaign(s) for {$today}.");
    }
}
