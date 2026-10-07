<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialMediaReportRequest;
use App\Models\AssignService;
use App\Models\Client;
use App\Models\SocialMediaReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SocialMediaReportController extends Controller
{
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
            ->get();

        return view('social_media_reports.client_services', compact('client', 'assignServices'));
    }

    public function assignServicesByClient(Client $client)
    {
        $rows = AssignService::where('client_id', $client->id)
            ->orderByDesc('from_date')
            ->get()
            ->map(fn ($a) => [
                'id'        => $a->id,
                'title'     => $a->title ?: $a->service_names ?: ('Service #' . $a->id),
                'from_date' => optional($a->from_date)->format('Y-m-d'),
                'to_date'   => optional($a->to_date)->format('Y-m-d'),
            ]);

        return response()->json($rows);
    }

    /* ============================================================
     |  serviceMetrics — previous + today, with date-range guard
     ============================================================ */
    public function serviceMetrics(AssignService $assignService)
    {
        $assignService->load('client');

        $previousReports = SocialMediaReport::whereJsonContains('assign_service_ids', $assignService->id)
            ->whereDate('created_at', '<', Carbon::today())
            ->orderByDesc('created_at')
            ->get();

        $todayReports = SocialMediaReport::whereJsonContains('assign_service_ids', $assignService->id)
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
        SocialMediaReport::create($request->validated());

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

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports' => collect([$report]),
            'single'  => true,
            'filters' => [],
        ])->setPaper('a4', 'portrait');

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
            'reports' => $reports,
            'single'  => false,
            'filters' => $request->only(['from', 'to']),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('social-media-reports-all-' . now()->format('Ymd-His') . '.pdf');
    }

    /* ============================================================
     |  JSON list for assign-services multi-select
     ============================================================ */
    public function assignServicesList()
    {
        return AssignService::orderBy('id')
            ->get()
            ->map(fn ($a) => [
                'id'    => $a->id,
                'title' => $a->title ?: $a->service_names ?: ('Service #' . $a->id),
            ])
            ->values();
    }
}