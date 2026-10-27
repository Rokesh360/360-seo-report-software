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

    public function aiReport(Request $request, MetaAdsReportService $reports)
    {
        $ranges = ['today' => 'Today'] + MetaCatalogService::RANGES;
        $data = $request->validate([
            'range' => ['required', 'in:' . implode(',', array_keys($ranges))],
            'since' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d', 'before_or_equal:today'],
            'until' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d', 'after_or_equal:since', 'before_or_equal:today'],
        ]);

        $today = Carbon::today();

        [$from, $to] = match ($data['range']) {
            'today'      => [$today->copy(), $today->copy()],
            'yesterday'  => [$today->copy()->subDay(), $today->copy()->subDay()],
            'last_7d'    => [$today->copy()->subDays(6), $today->copy()],
            'last_14d'   => [$today->copy()->subDays(13), $today->copy()],
            'last_30d'   => [$today->copy()->subDays(29), $today->copy()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'custom' => [
                Carbon::createFromFormat('Y-m-d', $data['since']),
                Carbon::createFromFormat('Y-m-d', $data['until']),
            ],
        };

        $payload = $reports->buildPayload($from, $to);

        if (! $reports->hasData($payload)) {
            return back()->withInput()->withErrors([
                'from' => 'No saved Meta Ads data exists for the selected date range.',
            ]);
        }

        try {
            $report = $reports->generate($payload);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['from' => $e->getMessage()]);
        }

        return Pdf::loadView('meta_catalog.ai_report_pdf', [
            'payload' => $payload,
            'report'  => $report,
        ])
            ->setPaper('a4', 'portrait')
            ->download("meta-ads-ai-report-{$from->toDateString()}-to-{$to->toDateString()}.pdf");
    }
}
