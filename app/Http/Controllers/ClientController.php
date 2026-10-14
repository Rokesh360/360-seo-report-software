<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Repositories\ClientRepository;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(private ClientRepository $clients) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],
        ]);

        return view('clients.index', [
            'clients' => $this->clients->paginate(
                10,
                $filters['from_date'] ?? null,
                $filters['to_date'] ?? null
            ),
            'fromDate' => $filters['from_date'] ?? '',
            'toDate' => $filters['to_date'] ?? '',
        ]);
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(ClientRequest $request)
    {
        $this->clients->create($request->validated());

        return redirect()->route('clients.index')->with('success', 'Client added.');
    }

    public function edit(int $id)
    {
        return view('clients.edit', ['client' => $this->clients->find($id)]);
    }

    public function update(ClientRequest $request, int $id)
    {
        $this->clients->update($id, $request->validated());

        return redirect()->route('clients.index')->with('success', 'Client updated.');
    }

    public function destroy(int $id)
    {
        if (! $this->clients->delete($id)) {
            return redirect()->route('clients.index')
                ->with('error', 'Cannot delete: this client has SEO reports. Set status to inactive instead.');
        }

        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }
}
