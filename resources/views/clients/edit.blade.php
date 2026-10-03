@include('clients._form', [
    'client' => $client,
    'action' => route('clients.update', $client->id),
    'method' => 'PUT',
    'title'  => 'Edit Client',
])