<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialMediaReportRequest;
use App\Models\AssignService;
use App\Models\Service;
use App\Models\Client;
use App\Models\SocialMediaReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SocialMediaReportController extends Controller
{

    private function reportTypesFor($reports, array $assignServices): array
    {
        $nameToType = [
            'WhatsApp Meta Ads Monthly Report'       => 'whatsapp_meta_ads',
            'Lead Generation Ads Monthly Report'     => 'lead_generation_ads',
            'Social Media Engagement Monthly Report' => 'social_media_engagement',
        ];

        $serviceNames = Service::pluck('name', 'id');   // [id => name]
        $result = [];

        foreach ($reports as $report) {
            $types = collect((array) $report->assign_service_ids)
                ->map(fn($id) => $assignServices[(int) $id] ?? null)
                ->filter()
                ->flatMap(function ($as) {
                    $ids = $as->service_id;
                    return is_array($ids) ? $ids : array_filter([(int) $ids]);
                })
                ->map(fn($sid) => $nameToType[$serviceNames[$sid] ?? ''] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $result[$report->id] = $types;
        }

        return $result;
    }
    /* ============================================================
     |  INDEX — client cards
     ============================================================ */
    public function index()
    {
        $clients = Client::withCount('assignServices')
            ->orderBy('name')
            ->get();

        return view('social_media_reports.index', compact('clients'));
    }

    /* ============================================================
     |  Client services
     ============================================================ */
    public function clientServices(Client $client)
    {
        $assignServices = AssignService::where('client_id', $client->id)
            ->latest('from_date')
            ->get()
            ->each(function (AssignService $a) {
                $from = $a->from_date->copy()->startOfDay();
                $to   = $a->to_date->copy()->endOfDay();

                // number of table rows = number of dates in the range
                $a->days_total = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;

                // dates that already have a report (by created_at)
                $a->days_filled = SocialMediaReport::forAssignService($a->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->pluck('created_at')
                    ->map(fn($d) => $d->toDateString())
                    ->unique()
                    ->count();
            });

        return view('social_media_reports.client_services', compact('client', 'assignServices'));
    }

    public function assignServicesByClient(Client $client)
    {
        $rows = AssignService::where('client_id', $client->id)
            ->orderByDesc('from_date')
            ->get()
            ->map(fn($a) => [
                'id'                 => $a->id,
                'title'              => $a->title ?: $a->service_names ?: ('Service #' . $a->id),
                'from_date'          => optional($a->from_date)->format('Y-m-d'),
                'to_date'            => optional($a->to_date)->format('Y-m-d'),
                'platforms'          => $a->platforms,
                'campaign_objective' => $a->campaign_objective,
                'campaign_overview'  => $a->campaign_overview,
                'target_audience'    => $a->target_audience,
                'campaign_focus'     => $a->campaign_focus,
                'key_highlights'     => $a->key_highlights,
            ]);

        return response()->json($rows);
    }

    public function previousMetrics(AssignService $assignService)
    {
        $assignService->load('client');

        $from = $assignService->from_date->copy()->startOfDay();
        $to   = $assignService->to_date->copy()->startOfDay();

        $byDate = SocialMediaReport::forAssignService($assignService->id)
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn($r) => $r->created_at->toDateString());

        $rows = collect();
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $rows->push([
                'date'   => $d->copy(),
                'report' => $byDate->get($d->toDateString())?->first(),
                'future' => $d->gt(Carbon::today()),
            ]);
        }

        return view('social_media_reports.previous_metrics', [
            'assignService' => $assignService,
            'rows'          => $rows,
        ]);
    }

    /* ============================================================
     |  serviceMetrics — previous + today, with date-range guard
     ============================================================ */
    public function serviceMetrics(AssignService $assignService)
    {
        $assignService->load('client');

        $previousReports = SocialMediaReport::forAssignService($assignService->id)
            ->whereDate('created_at', '<', Carbon::today())
            ->orderByDesc('created_at')
            ->get();

        $todayReports = SocialMediaReport::forAssignService($assignService->id)
            ->whereDate('created_at', Carbon::today())
            ->orderByDesc('id')
            ->get();

        $todayEntryMissing = $todayReports->isEmpty();

        $canAddToday = ($assignService->status ?? true)
            && Carbon::today()->betweenIncluded($assignService->from_date, $assignService->to_date);

        return view('social_media_reports.service_metrics', compact(
            'assignService',
            'previousReports',
            'todayReports',
            'todayEntryMissing',
            'canAddToday'
        ));
    }

    /* ============================================================
     |  create / store
     ============================================================ */
    public function create(Request $request)
    {
        $assignServices = AssignService::orderBy('id')->get();

        $prefillId = $request->integer('assign_service_id') ?: null;

        return view('social_media_reports.create', compact('assignServices', 'prefillId'));
    }

    public function store(SocialMediaReportRequest $request)
    {
        $report = new SocialMediaReport($request->validated());

        // date comes from ?date=, keep current time for same-day ordering
        $report->created_at = $request->reportDate()->setTimeFrom(now());
        $report->save();

        return redirect()
            ->route('social-media-reports.index')
            ->with('success', 'Report created successfully.');
    }

    /* ============================================================
     |  edit / update / destroy
     ============================================================ */
    public function edit(SocialMediaReport $socialMediaReport)
    {
        $assignServices = AssignService::orderBy('id')->get();

        return view('social_media_reports.edit', [
            'report'         => $socialMediaReport,
            'assignServices' => $assignServices,
        ]);
    }

    public function update(SocialMediaReportRequest $request, SocialMediaReport $socialMediaReport)
    {
        $socialMediaReport->update($request->validated());

        return redirect()
            ->route('social-media-reports.index')
            ->with('success', 'Report updated successfully.');
    }

    public function destroy(SocialMediaReport $socialMediaReport)
    {
        $socialMediaReport->delete();

        return redirect()
            ->route('social-media-reports.index')
            ->with('success', 'Report moved to trash.');
    }

    /* ============================================================
     |  PDF exports
     ============================================================ */
    public function exportPdf(int $id)
    {
        $report = SocialMediaReport::findOrFail($id);
        $reports = collect([$report]);
        $assignServices = $this->assignServicesFor($reports);

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports'     => $reports,
            'single'      => true,
            'filters'     => [],
            'assignServices' => $assignServices,
            'reportTypes'    => $this->reportTypesFor($reports, $assignServices),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true]);

        return $pdf->download('social-report-' . $report->id . '.pdf');
    }

    public function exportAllPdf(Request $request)
    {
        $query = SocialMediaReport::query();

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $reports = $query->orderBy('created_at')->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'No reports found for the selected filters.');
        }

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports'     => $reports,
            'single'      => false,
            'filters'     => $request->only(['from', 'to']),
            'assignServices' => $this->assignServicesFor($reports),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true]);

        return $pdf->download('social-media-reports-all-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * Load assigned services by ID so PDF report details come from assignments.
     */
    private function assignServicesFor($reports): array
    {
        $ids = $reports
            ->flatMap(function (SocialMediaReport $report) {
                $ids = $report->assign_service_ids;

                return is_array($ids) ? $ids : [];
            })
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

    /* ============================================================
     |  JSON list for assign-services multi-select
     ============================================================ */
    public function assignServicesList()
    {
        return AssignService::orderBy('id')
            ->get()
            ->map(fn($a) => [
                'id'                 => $a->id,
                'title'              => $a->title ?: $a->service_names ?: ('Service #' . $a->id),
                'platforms'          => $a->platforms,
                'campaign_objective' => $a->campaign_objective,
                'campaign_overview'  => $a->campaign_overview,
                'target_audience'    => $a->target_audience,
                'campaign_focus'     => $a->campaign_focus,
                'key_highlights'     => $a->key_highlights,
            ])
            ->values();
    }

    public function exportServicePdf(AssignService $assignService)
    {
        $assignService->load('client');

        $from = $assignService->from_date->copy()->startOfDay();
        $to   = $assignService->to_date->copy()->endOfDay();

        // Only filled reports inside the assignment's date range
        $reports = SocialMediaReport::forAssignService($assignService->id)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'No filled reports found within this date range.');
        }

        $assignServices = $this->assignServicesFor($reports);

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports'        => $reports,
            'single'         => false,
            'filters'        => [
                'client_name' => $assignService->client?->name,
                'from'        => $from->format('d M Y'),
                'to'          => $to->format('d M Y'),
            ],
            'assignServices' => $assignServices,
            'reportTypes'    => $this->reportTypesFor($reports, $assignServices),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true]);

        $name = Str::slug(($assignService->client?->name ?? 'client') . '-' . ($assignService->title ?: 'service'));

        return $pdf->download(
            $name . '-overall-report-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.pdf'
        );
    }
}
