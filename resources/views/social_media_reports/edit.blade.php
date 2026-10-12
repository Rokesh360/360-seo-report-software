@include('social_media_reports._form', [
    'report' => $report,
    'action' => route('daily-metrics-reports.update', ['daily_metrics_report' => $report->getRouteKey()]),
    'method' => 'PUT',
    'title'  => 'Edit Social Media Report',
])