<?php

namespace App\Http\Controllers;

use App\Services\MetaCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\MetaAdsReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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

    public function exportPdf(Request $request, MetaCatalogService $service)
    {
        $ranges = ['today' => 'Today'] + MetaCatalogService::RANGES;

        $filters = $request->validate([
            'range' => ['required', 'in:' . implode(',', array_keys($ranges))],
            'since' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d', 'before_or_equal:today'],
            'until' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d', 'after_or_equal:since', 'before_or_equal:today'],
        ]);

        // "Today" is sent to the service as a one-day custom range (same as index())
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
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['range' => $e->getMessage()]);
        }

        $summary  = $data['summary']['data'][0] ?? [];
        $currency = $summary['account_currency']
            ?? ($data['campaigns']['data'][0]['account_currency'] ?? '');

        $campaigns = collect($data['campaigns']['data'] ?? [])
            ->filter(fn($c) => is_array($c))
            ->sortByDesc(fn($c) => (float) ($c['spend'] ?? 0))
            ->values()
            ->map(function ($c) {
                $c['_result'] = $this->primaryResult($c);
                return $c;
            });

        $daily = collect($data['daily']['data'] ?? [])
            ->filter(fn($d) => is_array($d) && isset($d['date_start']))
            ->sortBy('date_start')
            ->values();

        $label = $ranges[$filters['range']];
        $periodText = $filters['range'] === 'custom'
            ? Carbon::parse($filters['since'])->format('d M Y') . ' — ' . Carbon::parse($filters['until'])->format('d M Y')
            : (isset($summary['date_start'])
                ? Carbon::parse($summary['date_start'])->format('d M Y') . ' — ' . Carbon::parse($summary['date_stop'] ?? $summary['date_start'])->format('d M Y')
                : $label);

        $fileSuffix = $filters['range'] === 'custom'
            ? "{$filters['since']}-to-{$filters['until']}"
            : $filters['range'] . '-' . now()->toDateString();

        return Pdf::loadView('meta_catalog.export_pdf', [
            'summary'    => $summary,
            'currency'   => $currency,
            'campaigns'  => $campaigns,
            'daily'      => $daily,
            'rangeLabel' => $label,
            'periodText' => $periodText,
        ])
            ->setPaper('a4', 'portrait')
            ->download("meta-ads-report-{$fileSuffix}.pdf");
    }

    /** Pull the primary result out of Meta's actions array (same logic as the dashboard). */
    private function primaryResult(array $c): ?array
    {
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

        $actions = collect($c['actions'] ?? [])
            ->filter(fn($a) => isset($a['action_type'], $a['value']))
            ->keyBy('action_type');

        foreach ($map as $type => $label) {
            if ($actions->has($type)) {
                return ['label' => $label, 'value' => (float) $actions[$type]['value']];
            }
        }

        return null;
    }
}
