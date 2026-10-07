<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignServiceRequest;
use App\Models\AssignService;
use App\Models\Client;
use App\Models\Service;
use App\Repositories\AssignServiceRepository;
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

    public function index(): View
    {
        $assignServices = $this->repository->paginate(15);

        return view('assign-services.index', [
            'assignServices' => $assignServices,
            'active'         => 'assign-services',
        ]);
    }

    public function create(): View
    {
        return view('assign-services.create', [
            'clients'  => Client::orderBy('name')->get(),
            'services' => Service::orderBy('name')->get(),
            'active'   => 'assign-services',
        ]);
    }

    public function store(AssignServiceRequest $request): RedirectResponse
    {
        $this->repository->create($request->validated());

        return redirect()
            ->route('assign-services.index')
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
        return view('assign-services.edit', [
            'assignService' => $this->repository->find($id),
            'clients'       => Client::orderBy('name')->get(),
            'services'      => Service::orderBy('name')->get(),
            'active'        => 'assign-services',
        ]);
    }

    public function update(AssignServiceRequest $request, int $id): RedirectResponse
    {
        $assignService = $this->repository->find($id);
        $this->repository->update($assignService, $request->validated());

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
        ]);
    }
}
