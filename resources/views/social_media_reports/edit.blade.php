@include('social_media_reports._form', [
    'report' => $report,
    'action' => route('social-media-reports.update', $report->id),
    'method' => 'PUT',
    'title'  => 'Edit Social Media Report',
])