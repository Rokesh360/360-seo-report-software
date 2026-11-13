<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignServiceRequest;
use App\Models\AssignService;
use App\Models\Client;
use App\Models\Service;
use App\Repositories\AssignServiceRepository;
use App\Support\Helpers;
use App\Services\MetaCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignServiceController extends Controller
{
    public function __construct(
        protected AssignServiceRepository $repository
    ) {
        //
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'client' => ['sometimes', 'integer', 'exists:clients,id'],
        ]);
        $clientId = isset($validated['client']) ? (int) $validated['client'] : null;
        $clients = Client::withCount('assignServices')->orderBy('name')->get();
        $assignServices = $this->repository->paginate(15, $clientId)
            ->appends($request->query());

        return view('assign-services.index', [
            'assignServices' => $assignServices,
            'clients'        => $clients,
            'selectedClientId' => $clientId,
            'active'         => 'assign-services',
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $clientId = $request->integer('client') ?: null;

        if (! $clientId || ! Client::whereKey($clientId)->exists()) {
            return redirect()
                ->route('assign-services.index')
                ->with('error', 'Please select a client first.');
        }

        $client = Client::findOrFail($clientId);

        return view('assign-services.create', [
            'client'           => $client,
            'services'         => Service::orderBy('name')->get(),
            'active'           => 'assign-services',
            'selectedClientId' => $clientId,
        ]);
    }

    public function store(AssignServiceRequest $request): RedirectResponse
    {
        $data = Helpers::cleanCampaigns($request->validated());

        // Force the client from the query string / request — never trust the form
        $data['client_id'] = $request->integer('client')
            ?: $request->integer('client_id');

        $this->repository->create($data);

        return redirect()
            ->route('assign-services.index', ['client' => $data['client_id']])
            ->with('success', 'Service assigned to client successfully.');
    }

    public function show(int $id): View
    {
        return view('assign-services.show', [
            'assignService' => $this->repository->find($id),
            'active'        => 'assign-services',
        ]);
    }

    public function edit(int $id): View
    {
        $assignService = $this->repository->find($id);

        return view('assign-services.edit', [
            'assignService'    => $assignService,
            'client'           => $assignService->client,
            'services'         => Service::orderBy('name')->get(),
            'active'           => 'assign-services',
            'selectedClientId' => $assignService->client_id,
        ]);
    }

    public function update(AssignServiceRequest $request, int $id): RedirectResponse
    {
        $assignService = $this->repository->find($id);
        $this->repository->update($assignService, Helpers::cleanCampaigns($request->validated()));

        return redirect()
            ->route('assign-services.index')
            ->with('success', 'Assigned service updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $assignService = $this->repository->find($id);
        $this->repository->delete($assignService);

        return redirect()
            ->route('assign-services.index')
            ->with('success', 'Assigned service deleted successfully.');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'date'      => ['required', 'date'],
        ]);

        $assignment = AssignService::with('service')
            ->where('client_id', $request->integer('client_id'))
            ->active()
            ->coveringDate($request->date('date'))
            ->orderByDesc('from_date')
            ->first();

        if (! $assignment) {
            return response()->json([
                'found'   => false,
                'message' => 'No active service assignment found for this client on the selected date.',
            ]);
        }

        return response()->json([
            'found'       => true,
            'id'          => $assignment->id,
            'title'       => $assignment->title,
            'service_ids' => $assignment->service_id,          // array
            'services'    => $assignment->service_names,        // "SEO, Google Ads"
            'from_date'   => $assignment->from_date?->format('Y-m-d'),
            'to_date'     => $assignment->to_date?->format('Y-m-d'),
            'duration'    => $assignment->duration,
            'platforms'          => $assignment->platforms,
            'campaign_objective' => $assignment->campaign_objective,
            'campaign_overview'  => $assignment->campaign_overview,
            'target_audience'    => $assignment->target_audience,
            'campaign_focus'     => $assignment->campaign_focus,
            'key_highlights'     => $assignment->key_highlights,
        ]);
    }

    public function campaigns(Request $request, MetaCatalogService $meta)
    {
        $validated = $request->validate([
            'service_id' => ['required', 'array', 'min:1'],
            'service_id.*' => ['required', 'integer', 'exists:services,id'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);

        $hasCampaignService = Service::query()
            ->whereIn('id', $validated['service_id'])
            ->where('is_campaign_available', true)
            ->exists();

        if (! $hasCampaignService) {
            return response()->json([
                'message' => 'Select a service that supports Meta Ads campaigns.',
            ], 422);
        }

        $result = $meta->campaignsForDateRange($validated['from_date'], $validated['to_date']);

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['error'] ?? 'Could not load Meta Ads campaigns.',
            ], 502);
        }

        $campaigns = collect($result['data'] ?? [])
            ->filter(fn ($campaign) => is_array($campaign)
                && ! empty($campaign['campaign_id'])
                && isset($campaign['spend'])
                && is_numeric($campaign['spend']))
            ->map(fn (array $campaign) => [
                'campaign_id' => (string) $campaign['campaign_id'],
                'campaign_name' => (string) ($campaign['campaign_name'] ?? $campaign['campaign_id']),
                'total_spend' => (float) $campaign['spend'],
                'currency' => (string) ($campaign['account_currency'] ?? 'INR'),
                'types' => Helpers::campaignTypesFromApi($campaign),
            ])
            ->sortBy('campaign_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'campaigns' => $campaigns,
            'gst_percentage' => Helpers::gstPercentage(),
        ]);
    }
}
