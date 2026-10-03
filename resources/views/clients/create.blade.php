@include('clients._form', [
    'client' => null,
    'action' => route('clients.store'),
    'method' => 'POST',
    'title'  => 'Add Client',
])