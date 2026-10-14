@include('social_media_reports._form', [
    'report' => $report,
    'action' => route('metrics-reports.update', ['metrics_report' => $report->getRouteKey()]),
    'method' => 'PUT',
    'title'  => 'Edit Social Media Report',
])