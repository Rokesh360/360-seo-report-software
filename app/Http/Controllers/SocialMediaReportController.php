<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialMediaReportRequest;
use App\Models\AssignService;
use App\Models\Client;
use App\Models\GstMaster;
use App\Models\Service;
use App\Support\Helpers;
use App\Models\SocialMediaReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SocialMediaReportController extends Controller
{

    /* ============================================================
     |  INDEX — client cards
     ============================================================ */
    public function index()
    {
        $clients = Client::withCount([
            'assignServices' => fn($q) => $q->forSocialReports(),
        ])
            ->orderBy('name')
            ->get();

        return view('social_media_reports.index', compact('clients'));
    }

    /* ============================================================
     |  Client services
     ============================================================ */
    public function clientServices(Client $client)
    {
        $assignServices = AssignService::forSocialReports()
            ->where('client_id', $client->id)
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
        $rows = AssignService::forSocialReports()
            ->where('client_id', $client->id)
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
            'gstPercentage' => Helpers::gstPercentage(),
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

        $assignServices = AssignService::forSocialReports()->orderBy('id')->get();

        return view('social_media_reports.create', [
            'assignServices' => $assignServices,
            'prefillId'      => $prefillId,
            'gstPercentage'  => Helpers::gstPercentage(),
        ]);
    }

    public function store(SocialMediaReportRequest $request)
    {
        $report = new SocialMediaReport($request->validated());
        $report->save();

        return redirect()
            ->route('metrics-reports.previous-metrics', $report->assign_service_id)
            ->with('success', 'Monthly report created successfully.')
            ->with('spend_summary', Helpers::spendSummary($report->fresh('assignService')));
    }

    /* ============================================================
     |  edit / update / destroy
     ============================================================ */
    public function edit(SocialMediaReport $metricsReport)
    {
        $assignServices = AssignService::forSocialReports()->orderBy('id')->get();

        return view('social_media_reports.edit', [
            'report'         => $metricsReport,
            'assignServices' => $assignServices,
            'gstPercentage'  => Helpers::gstPercentage(),
        ]);
    }

    public function update(SocialMediaReportRequest $request, SocialMediaReport $metricsReport)
    {
        $metricsReport->update($request->validated());

        return redirect()
            ->route('metrics-reports.previous-metrics', $metricsReport->assign_service_id)
            ->with('success', 'Report updated successfully.')
            ->with('spend_summary', Helpers::spendSummary($metricsReport->fresh('assignService')));
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
        $assignServices = Helpers::assignServicesFor($reports);

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports'     => $reports,
            'single'      => true,
            'filters'     => [],
            'assignServices' => $assignServices,
            'reportTypes'    => Helpers::reportTypesFor($reports, $assignServices),
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
            'assignServices' => Helpers::assignServicesFor($reports),
            'spendSummaries' => Helpers::spendSummariesFor($reports), 
        ])
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true]);

        return $pdf->download('metrics-reports-all-' . now()->format('Ymd-His') . '.pdf');
    }

    /* ============================================================
     |  JSON list of assign services
     ============================================================ */
    public function assignServicesList()
    {
        return AssignService::forSocialReports()
            ->orderBy('id')
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

        $assignServices = Helpers::assignServicesFor($reports);

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports'        => $reports,
            'single'         => false,
            'filters'        => [
                'client_name' => $assignService->client?->name,
                'from'        => $from->format('d M Y'),
                'to'          => $to->format('d M Y'),
            ],
            'assignServices' => $assignServices,
            'reportTypes'    => Helpers::reportTypesFor($reports, $assignServices),
            'spendSummaries' => Helpers::spendSummariesFor($reports),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true]);

        $name = Str::slug(($assignService->client?->name ?? 'client') . '-' . ($assignService->title ?: 'service'));

        return $pdf->download(
            $name . '-overall-report-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.pdf'
        );
    }
}
