@include('social_media_reports._form', [
    'report' => null,
    'action' => route('metrics-reports.store'),
    'method' => 'POST',
    'title'  => 'Create Social Media Report',
])