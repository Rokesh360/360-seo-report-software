@include('services._form', [
    'service' => $service,
    'action'  => route('services.update', $service->id),
    'method'  => 'PUT',
    'title'   => 'Edit Service',
])