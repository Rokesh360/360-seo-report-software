@include('assign-services._form', [
    'assignService' => $assignService,
    'action' => route('assign-services.update', $assignService->id),
    'method' => 'PUT',
    'title'  => 'Edit Assigned Service',
])