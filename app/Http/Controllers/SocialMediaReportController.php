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
    private function parseAmount(mixed $value): float
    {
        // "₹5,000.50" → 5000.50
        return (float) preg_replace('/[^0-9.]/', '', (string) $value);
    }

    private function spendSummary(SocialMediaReport $report): array
    {
        $budget = (float) ($report->assignService?->budget ?? 0);

        $spent = collect($report->campaign_results ?? [])
            ->sum(fn($row) => $this->parseAmount($row['spend'] ?? 0));

        return [
            'budget'    => $budget,
            'spent'     => $spent,
            'available' => $budget - $spent,
        ];
    }

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
            $assignment = $assignServices[(int) $report->assign_service_id] ?? null;
            $serviceIds = $assignment?->service_id;
            $types = collect(is_array($serviceIds) ? $serviceIds : array_filter([(int) $serviceIds]))
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
                // Monthly: one entry per service
                $a->has_report  = SocialMediaReport::forAssignService($a->id)->exists();
                $a->days_total  = 1;                       // kept so the existing view doesn't break
                $a->days_filled = $a->has_report ? 1 : 0;
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

        $report = SocialMediaReport::forAssignService($assignService->id)
            ->latest('id')
            ->first();

        // Monthly: always exactly one row (Add if no report, Edit + Delete if there is one)
        $rows = collect([[
            'date'   => $report?->created_at,
            'report' => $report,
            'future' => false,
        ]]);

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

        $report = SocialMediaReport::forAssignService($assignService->id)
            ->latest('id')
            ->first();

        // Variable names kept for the existing service_metrics view
        $previousReports   = collect();
        $todayReports      = $report ? collect([$report]) : collect();
        $todayEntryMissing = ! $report;
        $canAddToday       = ! $report && ($assignService->status ?? true);

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
        $prefillId = $request->integer('assign_service_id') ?: null;

        // Already has its monthly entry → send to edit instead
        if ($prefillId) {
            $existing = SocialMediaReport::forAssignService($prefillId)->latest('id')->first();
            if ($existing) {
                return redirect()
                    ->route('metrics-reports.edit', $existing->id)
                    ->with('error', 'The monthly report already exists. You can edit it here.');
            }
        }

        $assignServices = AssignService::orderBy('id')->get();

        return view('social_media_reports.create', compact('assignServices', 'prefillId'));
    }

    public function store(SocialMediaReportRequest $request)
    {
        $report = new SocialMediaReport($request->validated());
        $report->save();

        return redirect()
            ->route('metrics-reports.index')
            ->with('success', 'Monthly report created successfully.')
            ->with('spend_summary', $this->spendSummary($report));
    }

    /* ============================================================
     |  edit / update / destroy
     ============================================================ */
    public function edit(SocialMediaReport $metricsReport)
    {
        $assignServices = AssignService::orderBy('id')->get();

        return view('social_media_reports.edit', [
            'report'         => $metricsReport,
            'assignServices' => $assignServices,
        ]);
    }

    public function update(SocialMediaReportRequest $request, SocialMediaReport $metricsReport)
    {
        $metricsReport->update($request->validated());

        return redirect()
            ->route('metrics-reports.index')
            ->with('success', 'Report updated successfully.');
    }

    public function destroy(SocialMediaReport $metricsReport)
    {
        $metricsReport->delete();

        return redirect()
            ->route('metrics-reports.index')
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

        return $pdf->download('metrics-reports-all-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * Load assigned services by ID so PDF report details come from assignments.
     */
    private function assignServicesFor($reports): array
    {
        $ids = $reports
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

    /* ============================================================
     |  JSON list of assign services
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

        $reports = SocialMediaReport::forAssignService($assignService->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'No report found for this service.');
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
