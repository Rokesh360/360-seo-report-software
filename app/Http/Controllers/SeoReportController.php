<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeoReportRequest;
use App\Repositories\ClientRepository;
use App\Repositories\SeoReportRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SeoReportController extends Controller
{
    public function __construct(
        private SeoReportRepository $reports,
        private ClientRepository $clients
    ) {
    }

    // Table listing
    public function index(Request $request)
    {
        $validated = $request->validate([
            'client' => ['sometimes', 'integer', 'exists:clients,id'],
        ]);
        $clientId = isset($validated['client']) ? (int) $validated['client'] : null;

        return view('seo_reports.index', [
            'clients' => $this->clients->withSeoReportCounts(),
            'selectedClientId' => $clientId,
            'reports' => $this->reports->paginate(clientId: $clientId)->appends($request->query()),
        ]);
    }

    public function trash()
    {
        return view('seo_reports.trash', [
            'reports' => $this->reports->paginateTrashed(),
        ]);
    }

    public function create()
    {
        return view('seo_reports.create', [
            'clients' => $this->clients->activeList(),   // client dropdown
        ]);
    }

    // Save form + multiple rows + image uploads
    public function store(SeoReportRequest $request)
    {
        $this->reports->create($request->validated());

        return redirect()->route('seo-reports.index')
            ->with('success', 'SEO report saved successfully.');
    }

    public function edit(int $id)
    {
        return view('seo_reports.edit', [
            'report'  => $this->reports->find($id),
            'clients' => $this->clients->activeList(),   // client dropdown
        ]);
    }

    public function update(SeoReportRequest $request, int $id)
    {
        $this->reports->update($id, $request->validated());

        return redirect()->route('seo-reports.index')
            ->with('success', 'SEO report updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->reports->delete($id);

        return redirect()->route('seo-reports.index')
            ->with('success', 'SEO report moved to trash.');
    }

    public function restore(int $id)
    {
        $this->reports->restore($id);

        return redirect()->route('seo-reports.trash')
            ->with('success', 'SEO report restored.');
    }

    // Export PDF
    public function exportPdf(int $id)
    {
        $report = $this->reports->find($id);

        $pdf = Pdf::loadView('seo_reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        $name = str($report->client->name)->slug('_') . '_' . $report->reporting_period . '_SEO_MONTHLY_REPORT.pdf';

        return $pdf->download($name);
    }
}