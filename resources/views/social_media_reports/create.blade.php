@include('social_media_reports._form', [
    'report' => null,
    'action' => route('social-media-reports.store'),
    'method' => 'POST',
    'title'  => 'Create Social Media Report',
])