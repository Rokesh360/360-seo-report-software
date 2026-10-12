<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeoReportRequest;
use App\Models\Client;
use App\Repositories\ClientRepository;
use App\Repositories\SeoReportRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoReportController extends Controller
{
    public function __construct(
        private SeoReportRepository $reports,
        private ClientRepository $clients
    ) {}

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

    public function create(Request $request): View|RedirectResponse
    {
        $clientId = $request->integer('client') ?: null;

        if (! $clientId || ! Client::whereKey($clientId)->exists()) {
            return redirect()
                ->route('seo-reports.index')
                ->with('error', 'Please select a client first.');
        }

        $client = Client::findOrFail($clientId);

        return view('seo_reports.create', [
            'client'           => $client,
            'selectedClientId' => $clientId,
            'active'           => 'reports',
        ]);
    }

    // Save form + multiple rows + image uploads
    public function store(SeoReportRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Client comes from the route query string — never trust a hidden field blindly
        $data['client_id'] = $request->integer('client')
            ?: $request->integer('client_id');

        // Website mirrors the client's website (read-only in the form)
        $data['website'] = Client::whereKey($data['client_id'])->value('website') ?? '';

        $this->reports->create($data);

        return redirect()
            ->route('seo-reports.index', ['client' => $data['client_id']])
            ->with('success', 'SEO report saved successfully.');
    }

    public function edit(int $id): View
    {
        $report = $this->reports->find($id);

        return view('seo_reports.edit', [
            'report'           => $report,
            'client'           => $report->client,
            'selectedClientId' => $report->client_id,
            'active'           => 'reports',
        ]);
    }

    public function update(SeoReportRequest $request, int $id): RedirectResponse
    {
        $data = $request->validated();

        // Force client_id from the existing record (edit form never changes client)
        $report = $this->reports->find($id);
        $data['client_id'] = $report->client_id;
        $data['website']   = $report->client->website ?? '';

        $this->reports->update($id, $data);

        return redirect()
            ->route('seo-reports.index', ['client' => $report->client_id])
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
