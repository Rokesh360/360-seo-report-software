<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleAdsReportRequest;
use App\Models\AssignService;
use App\Models\Client;
use App\Models\GoogleAdsReport;
use App\Support\ReportServiceType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GoogleAdsReportController extends Controller
{
    public function index(): View
    {
        $assignments = AssignService::with('client')->get()
            ->filter(fn (AssignService $assignment) => $this->googleAdsServices($assignment)->isNotEmpty());
        $clients = Client::query()->whereIn('id', $assignments->pluck('client_id')->unique())
            ->where('status', true)->orderBy('name')->get();

        return view('google_ads.index', compact('clients', 'assignments'));
    }

    public function clientServices(Client $client): View
    {
        $assignments = AssignService::with('client')
            ->where('client_id', $client->id)
            ->orderByDesc('from_date')
            ->get()
            ->filter(fn (AssignService $assignment) => $this->googleAdsServices($assignment)->isNotEmpty())
            ->values();

        return view('google_ads.client_services', compact('client', 'assignments'));
    }

    public function previousMetrics(AssignService $assignService): View
    {
        $this->ensureGoogleAdsAssignment($assignService);
        $assignService->load('client');
        $report = GoogleAdsReport::where('assign_service_id', $assignService->id)->first();

        return view('google_ads.previous_metrics', compact('assignService', 'report'));
    }

    public function create(): View|RedirectResponse
    {
        $assignServiceId = request()->integer('assign_service_id');
        $assignService = AssignService::with('client')->findOrFail($assignServiceId);
        $this->ensureGoogleAdsAssignment($assignService);

        $existing = GoogleAdsReport::where('assign_service_id', $assignService->id)->first();
        if ($existing) {
            return redirect()->route('google-ads-reports.edit', $existing);
        }

        return view('google_ads.form', [
            'assignService' => $assignService,
            'report' => null,
            'formAction' => route('google-ads-reports.store'),
            'method' => 'POST',
        ]);
    }

    public function store(GoogleAdsReportRequest $request): RedirectResponse
    {
        $assignment = AssignService::with('client')->findOrFail($request->integer('assign_service_id'));
        $imagePaths = collect($request->file('overview_images', []))
            ->map(fn ($image) => $image->store('google-ads-overview', 'public'))
            ->all();
        $report = GoogleAdsReport::create([
            'assign_service_id' => $assignment->id,
            'data' => $this->addClickPercentages($request->validated('data')),
            'overview_images' => $imagePaths,
        ]);

        return redirect()->route('google-ads.assign-service', $assignment)
            ->with('success', 'Google Ads report saved.');
    }

    public function edit(GoogleAdsReport $googleAdsReport): View
    {
        $googleAdsReport->load('assignService.client');
        $assignment = $googleAdsReport->assignService;
        abort_if(! $assignment, 404);
        $this->ensureGoogleAdsAssignment($assignment);

        return view('google_ads.form', [
            'assignService' => $assignment,
            'report' => $googleAdsReport,
            'formAction' => route('google-ads-reports.update', $googleAdsReport),
            'method' => 'PUT',
        ]);
    }

    public function update(GoogleAdsReportRequest $request, GoogleAdsReport $googleAdsReport): RedirectResponse
    {
        $googleAdsReport->load('assignService.client');
        $existingImages = $googleAdsReport->overview_images ?? [];
        $removedImages = array_values(array_intersect(
            $existingImages,
            (array) $request->validated('remove_images', [])
        ));
        $imagePaths = array_values(array_diff($existingImages, $removedImages));
        foreach ($request->file('overview_images', []) as $image) {
            $imagePaths[] = $image->store('google-ads-overview', 'public');
        }

        $googleAdsReport->update([
            'data' => $this->addClickPercentages($request->validated('data')),
            'overview_images' => $imagePaths,
        ]);
        Storage::disk('public')->delete($removedImages);

        return redirect()->route('google-ads.assign-service', $googleAdsReport->assign_service_id)
            ->with('success', 'Google Ads report updated.');
    }

    public function destroy(GoogleAdsReport $googleAdsReport): RedirectResponse
    {
        $assignmentId = $googleAdsReport->assign_service_id;
        $googleAdsReport->delete();

        return redirect()->route('google-ads.assign-service', $assignmentId)
            ->with('success', 'Google Ads report deleted.');
    }

    public function exportPdf(GoogleAdsReport $googleAdsReport)
    {
        $googleAdsReport->load('assignService.client');
        $assignment = $googleAdsReport->assignService;
        abort_if(! $assignment, 404);
        $this->ensureGoogleAdsAssignment($assignment);
        $serviceType = $this->googleAdsServices($assignment)->first();

        return Pdf::loadView('google_ads.pdf.report', compact('googleAdsReport', 'assignment', 'serviceType'))
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
            ->download('google-ads-report-' . $googleAdsReport->id . '.pdf');
    }

    private function addClickPercentages(array $data): array
    {
        $totalClicks = (int) ($data['clicks'] ?? 0);
        $data['demographics'] = collect($data['demographics'] ?? [])
            ->map(function (array $row) use ($totalClicks): array {
                $clicks = (int) ($row['clicks'] ?? 0);
                $row['click_percentage'] = $totalClicks > 0 ? round($clicks / $totalClicks * 100, 1) : 0;

                return $row;
            })
            ->values()
            ->all();

        return $data;
    }

    private function ensureGoogleAdsAssignment(AssignService $assignment): void
    {
        abort_if($this->googleAdsServices($assignment)->isEmpty(), 404);
    }

    private function googleAdsServices(AssignService $assignment): Collection
    {
        return $assignment->getServices()
            ->map(fn ($service) => ReportServiceType::googleAdsTypeFromServiceName($service->name))
            ->filter()
            ->unique()
            ->values();
    }
}
