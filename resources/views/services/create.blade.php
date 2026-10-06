@include('services._form', [
    'service' => null,
    'action'  => route('services.store'),
    'method'  => 'POST',
    'title'   => 'Add Service',
])