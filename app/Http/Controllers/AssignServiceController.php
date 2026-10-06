<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignServiceRequest;
use App\Models\Client;
use App\Models\Service;
use App\Repositories\AssignServiceRepository;
use Illuminate\Http\RedirectResponse;
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
}