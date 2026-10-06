<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(
        protected ServiceRepository $repository
    ) {
    }

    public function index(Request $request)
    {
        $services = $this->repository->paginate(
            perPage: 15,
            filters: [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ]
        );

        return view('services.index', compact('services'));
    }

    public function create()
    {
        return view('services.create');
    }

    public function store(ServiceRequest $request)
    {
        $this->repository->create($request->validated());

        return redirect()
            ->route('services.index')
            ->with('success', 'Service created successfully.');
    }

    public function edit(Service $service)
    {
        return view('services.edit', compact('service'));
    }

    public function update(ServiceRequest $request, Service $service)
    {
        $this->repository->update($service, $request->validated());

        return redirect()
            ->route('services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        $this->repository->delete($service);

        return redirect()
            ->route('services.index')
            ->with('success', 'Service moved to trash.');
    }
}