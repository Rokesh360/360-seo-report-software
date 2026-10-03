<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Repositories\ClientRepository;

class ClientController extends Controller
{
    public function __construct(private ClientRepository $clients)
    {
    }

    public function index()
    {
        return view('clients.index', ['clients' => $this->clients->paginate()]);
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
