@include('seo_reports._form', [
    'report' => $report,
    'action' => route('seo-reports.update', $report->id),
    'method' => 'PUT',
    'title'  => 'Edit SEO Report',
])