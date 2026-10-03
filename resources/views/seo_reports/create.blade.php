@include('seo_reports._form', [
    'report' => null,
    'action' => route('seo-reports.store'),
    'method' => 'POST',
    'title'  => 'Create SEO Report',
])
