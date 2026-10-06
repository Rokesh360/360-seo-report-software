<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialMediaReportRequest;
use App\Models\Client;
use App\Models\SocialMediaReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SocialMediaReportController extends Controller
{
    public function index()
    {
        $reports = SocialMediaReport::with('client')
            ->latest()
            ->paginate(15);

        return view('social_media_reports.index', compact('reports'));
    }

    public function create()
    {
        // Load clients for the dropdown
        $clients = Client::orderBy('name')->get(['id', 'name']);

        return view('social_media_reports.create', compact('clients'));
    }

    public function store(SocialMediaReportRequest $request)
    {
        // No user_id injection — client_id comes from the form
        SocialMediaReport::create($request->validated());

        return redirect()
            ->route('social-media-reports.index')
            ->with('success', 'Report created successfully.');
    }

    public function edit(SocialMediaReport $socialMediaReport)
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);

        return view('social_media_reports.edit', [
            'report'  => $socialMediaReport,
            'clients' => $clients,
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

    public function exportPdf(int $id)
    {
        $report = SocialMediaReport::with('client')->findOrFail($id);

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports' => collect([$report]),
            'single'  => true,
            'filters' => [],
        ])->setPaper('a4', 'portrait');

        return $pdf->download('social-report-' . $report->id . '.pdf');
    }

    public function exportAllPdf(Request $request)
    {
        $query = SocialMediaReport::with('client');

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->string('report_type'));
        }

        // Filter by client_id instead of client_name
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('reporting_period_start', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('reporting_period_end', '<=', $request->date('to'));
        }

        $reports = $query
            ->join('clients', 'clients.id', '=', 'social_media_reports.client_id')
            ->orderBy('clients.name')
            ->orderBy('social_media_reports.reporting_period_start')
            ->select('social_media_reports.*')
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'No reports found for the selected filters.');
        }

        $filters = [
            'report_type' => $request->input('report_type'),
            'client_id'   => $request->input('client_id'),
            'from'        => $request->input('from'),
            'to'          => $request->input('to'),
        ];

        $pdf = Pdf::loadView('social_media_reports.pdf.report', [
            'reports' => $reports,
            'single'  => false,
            'filters' => $filters,
        ])->setPaper('a4', 'portrait');

        $filename = 'social-media-reports-all-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }
}